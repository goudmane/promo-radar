import http from 'node:http';
import dns from 'node:dns/promises';
import { chromium } from 'playwright';

const PORT=Number(process.env.PORT||4100);
const TOKEN=process.env.SCRAPER_TOKEN;
if (!TOKEN) throw Error('SCRAPER_TOKEN must be set');
const MAX_PAGES=50, MAX_ROWS=5000, AGENT='PromoRadarBot';
let active=0;
function safeUrl(raw,host) {
  const url=new URL(raw);
  if(url.protocol!=='https:' || url.hostname.toLowerCase()!==host.toLowerCase() ||
     url.username || url.password || url.port) throw Error('URL is outside allowed HTTPS host');
  return url;
}
async function publicHost(host) {
  const addresses=await dns.lookup(host,{all:true});
  if(!addresses.length) throw Error('Host has no address');
  for(const {address} of addresses) {
    const v=address.toLowerCase();
    if(v==='::1'||v.startsWith('fc')||v.startsWith('fd')||v.startsWith('fe80')||
       v.startsWith('::ffff:')||v==='0.0.0.0') throw Error('Private address denied');
    if(!v.includes(':')) {
      const [a,b]=v.split('.').map(Number);
      if(a===10||a===127||a===0||a>=224||(a===172&&b>=16&&b<=31)||
         (a===192&&b===168)||(a===169&&b===254)||(a===100&&b>=64&&b<=127)) throw Error('Private address denied');
    }
  }
}
async function robotsAllowed(url) {
  const controller=new AbortController();
  const timer=setTimeout(()=>controller.abort(),8000);
  try {
    const r=await fetch(url.origin+'/robots.txt',{signal:controller.signal,redirect:'error'});
    if(r.status===404) return true;
    if(!r.ok) return false;
    const rules=(await r.text()).slice(0,500000).split(/\r?\n/);
    let group=false, picked=false, allowed=true, best=-1;
    for(const line of rules) {
      const clean=line.split('#')[0].trim();
      const split=clean.indexOf(':'); if(split<0) continue;
      const key=clean.slice(0,split).toLowerCase(), value=clean.slice(split+1).trim();
      if(key==='user-agent') { group=value==='*'||value.toLowerCase()===AGENT.toLowerCase(); if(group) picked=true; continue; }
      if(!group||!picked||!['allow','disallow'].includes(key)||!value) continue;
      const rule=value.split('*')[0].replace(/\$$/,'');
      if(url.pathname.startsWith(rule)&&rule.length>=best) { best=rule.length; allowed=key==='allow'; }
    }
    return allowed;
  } finally { clearTimeout(timer); }
}
async function extract({url,allowedHost,profile}) {
  const start=safeUrl(url,allowedHost);
  await publicHost(allowedHost);
  if(!await robotsAllowed(start)) throw Error('robots.txt disallows this URL');
  const browser=await chromium.launch({headless:true});
  const context=await browser.newContext({userAgent:AGENT+'/0.1 (+contact site owner)',locale:'fr-MA'});
  const rows=[], seen=new Set(), max=Math.min(MAX_PAGES,Math.max(1,Number(profile.max_pages||1)));
  let next=start.toString();
  try {
    for(let i=0;next&&i<max&&rows.length<MAX_ROWS;i++) {
      const target=safeUrl(next,allowedHost);
      await publicHost(allowedHost);
      if(!await robotsAllowed(target)) break;
      const page=await context.newPage();
      await page.route('**/*',async route=>{
        try {
          const request=route.request(), resource=new URL(request.url());
          if(resource.protocol!=='https:' || (request.isNavigationRequest() && resource.hostname.toLowerCase()!==allowedHost.toLowerCase())) return route.abort();
          await publicHost(resource.hostname);
          return route.continue();
        } catch { return route.abort() }
      });
      try {
        await page.goto(target.toString(),{waitUntil:'domcontentloaded',timeout:30000});
        safeUrl(page.url(),allowedHost);
        await page.waitForTimeout(750);
        const result=await page.evaluate(({item,selectors,nextSelector})=>{
          const absolute=v=>{try{return new URL(v,document.baseURI).href}catch{return ''}};
          const read=(node,selector)=>{
            if(!selector)return '';
            const [css,attribute]=selector.split('@');
            const el=node.querySelector(css);
            return el ? (attribute?el.getAttribute(attribute):el.textContent)?.trim()||'' : '';
          };
          const blocks=item?[...document.querySelectorAll(item)].slice(0,1000):[];
          const picked=blocks.map(node=>Object.fromEntries(Object.entries(selectors||{}).map(([key,s])=>[key,read(node,s)])));
          const ld=[...document.querySelectorAll('script[type="application/ld+json"]')].flatMap(el=>{
            try {const raw=JSON.parse(el.textContent);return Array.isArray(raw)?raw:[raw]}catch{return []}
          });
          const products=[];
          const visit=(node,depth=0)=>{
            if(!node||typeof node!=='object'||depth>5)return;
            if(Array.isArray(node)){node.forEach(x=>visit(x,depth+1));return}
            if(node['@type']==='Product'||(Array.isArray(node['@type'])&&node['@type'].includes('Product'))) {
              const offer=Array.isArray(node.offers)?node.offers[0]:node.offers||{};
              products.push({title:node.name,description:node.description,brand:typeof node.brand==='object'?node.brand?.name:node.brand,
                sku:node.sku,price:offer.price,original_price:undefined,url:node.url||offer.url||document.URL,
                image_url:Array.isArray(node.image)?node.image[0]:node.image,currency:offer.priceCurrency,active:offer.availability?.includes('InStock')??true});
            }
            for(const value of Object.values(node))visit(value,depth+1);
          };
          ld.forEach(x=>visit(x));
          const items=(picked.length?picked:products).map(row=>({...row,
            url:absolute(row.url||document.URL),image_url:row.image_url?absolute(row.image_url):''}));
          return {rows:items,next:nextSelector?absolute(read(document,nextSelector+'@href')):''};
        },{item:profile.item_selector||'',selectors:profile.selectors||{},nextSelector:profile.next_selector||''});
        for(const row of result.rows) {
          if(!row.title||!row.price)continue;
          const key=row.sku||row.url;
          if(!seen.has(key)){seen.add(key);rows.push(row)}
        }
        next=result.next&&result.next!==target.toString()?safeUrl(result.next,allowedHost).toString():'';
      } finally { await page.close(); }
      await new Promise(resolve=>setTimeout(resolve,1200));
    }
    return {rows:rows.slice(0,MAX_ROWS),pages:Math.min(max,seen.size?max:1)};
  } finally { await context.close(); await browser.close(); }
}
http.createServer(async(req,res)=>{
  const send=(status,data)=>{res.writeHead(status,{'content-type':'application/json'});res.end(JSON.stringify(data))};
  if(req.url!=='/extract'||req.method!=='POST')return send(404,{error:'Not found'});
  if(req.headers.authorization!=='Bearer '+TOKEN)return send(401,{error:'Unauthorized'});
  if(active>=2)return send(429,{error:'Busy'});
  active++;
  try {
    let raw='';
    for await(const part of req){raw+=part;if(raw.length>100000)throw Error('Request too large')}
    const input=JSON.parse(raw);
    if(!input?.url||!input?.allowedHost||typeof input.profile!=='object')throw Error('Invalid profile');
    send(200,await extract(input));
  } catch(e) { send(422,{error:String(e.message||e)}) }
  finally { active--; }
}).listen(PORT,'0.0.0.0');
