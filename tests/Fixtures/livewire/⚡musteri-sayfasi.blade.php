<?php

use Livewire\Component;

// The customers page: its modal holds the nested customer form (musteri-formu).
new class extends Component
{
    public int $musteriSayisi = 12;
};
?>

<div>
    <p>{{ $musteriSayisi }} müşteri</p>
    <acun:modal name="musteri-ekle">
        <livewire:musteri-formu />
    </acun:modal>
</div>
