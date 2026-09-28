<h1>Offers matching your watches</h1>
@foreach ($alerts as $alert)
  <p><strong>{{ $alert->offer->title }}</strong><br>
  {{ number_format($alert->offer->price_minor / 100, 2) }} {{ $alert->offer->currency }}<br>
  <a href="{{ $alert->offer->url }}">View retailer</a></p>
@endforeach
<p>Review price and availability on the retailer site before buying.</p>
