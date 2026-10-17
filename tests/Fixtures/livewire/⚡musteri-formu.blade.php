<?php

use Livewire\Component;

// A customer form in the page's modal, written in modal parts: it re-renders on its own.
new class extends Component
{
    public string $musteri = 'Ayşe Yılmaz';
};
?>

<div>
    <acun:modal.header title="Yeni müşteri" description="Müşteri grubunu ve sorumlu temsilciyi seçin." />
    <acun:modal.body>{{ $musteri }}</acun:modal.body>
    <acun:modal.footer>
        <acun:modal.close><acun:button variant="white">Vazgeç</acun:button></acun:modal.close>
        <acun:button type="submit">Kaydet</acun:button>
    </acun:modal.footer>
</div>
