<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class SourceUrlPolicy {
    public function assertAllowed(string $url,string $host): void {
        $parts=parse_url($url);
        $actual=strtolower($parts['host']??'');
        $host=strtolower(trim($host));
        if (($parts['scheme']??'')!=='https' || !$actual || $actual!==$host
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || filter_var($actual,FILTER_VALIDATE_IP) || $actual==='localhost'
            || str_ends_with($actual,'.local')) {
            throw ValidationException::withMessages(['url'=>'A matching public HTTPS host is required.']);
        }
        $records=dns_get_record($actual,DNS_A|DNS_AAAA);
        $ips=array_map(fn ($record)=>$record['ip']??$record['ipv6']??'', $records?:[]);
        if (!$ips) throw ValidationException::withMessages(['url'=>'Host DNS lookup failed.']);
        foreach ($ips as $ip) {
            if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))
                throw ValidationException::withMessages(['url'=>'Private or reserved host address denied.']);
        }
    }
}
