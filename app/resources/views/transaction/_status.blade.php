@php
    $statusClass = 'badge text-bg-warning';

    if ($status == 'failed') {
        $statusClass = 'badge text-bg-danger';
    } elseif ($status == 'paid') {
        $statusClass = 'badge text-bg-success';
    }
@endphp

<span class="{{ $statusClass }}">
    {{ ucfirst($status) }}
</span>
