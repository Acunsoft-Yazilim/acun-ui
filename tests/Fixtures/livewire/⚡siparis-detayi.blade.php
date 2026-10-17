<?php

use Livewire\Component;

// An order's details in the page's drawer, written in drawer parts: it re-renders on its own.
new class extends Component
{
    public string $musteri = 'Ayşe Yılmaz';
};
?>

<div>
    <acun:drawer.header title="Sipariş #4821" description="Kadıköy deposu · 3 ürün" />
    <acun:drawer.body>{{ $musteri }}</acun:drawer.body>
    <acun:drawer.footer>
        <acun:drawer.close><acun:button variant="white">Kapat</acun:button></acun:drawer.close>
    </acun:drawer.footer>
</div>
