<?php

use Livewire\Component;

// The orders page: its drawer holds the nested order details (siparis-detayi).
new class extends Component
{
    public int $siparisSayisi = 48;
};
?>

<div>
    <p>{{ $siparisSayisi }} sipariş</p>
    <acun:drawer name="siparis-detay">
        <livewire:siparis-detayi />
    </acun:drawer>
</div>
