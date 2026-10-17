<?php

use Livewire\Component;

// The cart page: its modal holds a nested component with classic content (cart-line), not parts.
new class extends Component
{
    public int $urunSayisi = 3;
};
?>

<div>
    <p>{{ $urunSayisi }} ürün</p>
    <acun:modal name="sepet-satiri" title="Sepet satırı">
        <livewire:cart-line />
    </acun:modal>
</div>
