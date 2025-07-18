<ul>
    @foreach ($aggregator->merchants as $merchant)
        <li>{{ $merchant->name }}</li>
    @endforeach
</ul>
