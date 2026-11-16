<?php

use Livewire\Component;

new class extends Component
{
    public bool $saved = false;
};
?>

<div>
    @if ($saved)
        <p>Kaydedildi</p>
    @endif

    <acun:dropdown id="islem-menusu">
        <acun:slot:trigger><acun:button variant="white">İşlemler</acun:button></acun:slot:trigger>
        <acun:dropdown.item wire:click="$refresh">Yenile</acun:dropdown.item>
    </acun:dropdown>

    <div id="iade-bilgisi">
        <acun:popover title="İade kuralı">
            <acun:slot:trigger><acun:button variant="white">Bilgi</acun:button></acun:slot:trigger>
            Ürünler 14 gün içinde iade edilebilir.
        </acun:popover>
    </div>

    <acun:tooltip id="kargo-ipucu" text="Ücretsiz kargo"><button type="button">Kargo</button></acun:tooltip>

    <acun:tabs id="urun-sekmeleri" :tabs="['ozet' => 'Özet', 'teslimat' => 'Teslimat']">
        <acun:tabs.panel value="ozet">Özet</acun:tabs.panel>
        <acun:tabs.panel value="teslimat">Teslimat</acun:tabs.panel>
    </acun:tabs>

    <acun:accordion id="sss">
        <acun:accordion.item name="kargo" title="Kargo">İki gün içinde.</acun:accordion.item>
    </acun:accordion>

    <acun:card id="siparis-karti" collapsible title="Siparişler">12 sipariş</acun:card>
</div>
