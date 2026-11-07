<?php

use Livewire\Component;

new class extends Component
{
    public int $round = 0;
};
?>

<div>
    <div class="flex items-end justify-between">
        <h2>Çok satanlar {{ $round }}</h2>
        <acun:carousel.controls for="vitrin-cok-satanlar" counter />
    </div>
    <acun:carousel id="vitrin-cok-satanlar" :arrows="false" :loop="false" :pagination="false" label="Çok satanlar">
        @foreach (range(1, 4) as $i)
            <acun:carousel.item wire:key="urun-{{ $i }}" label="Ürün {{ $i }}"><p>Ürün {{ $i }}</p></acun:carousel.item>
        @endforeach
    </acun:carousel>
</div>
