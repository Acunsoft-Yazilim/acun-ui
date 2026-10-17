<?php

use Livewire\Component;

new class extends Component
{
    public int $quantity = 1;

    public function increase(): void
    {
        $this->quantity++;
    }
};
?>

<div>
    <acun:input wire:model="quantity" type="number" label="Adet" />
    <acun:button variant="success" wire:click="increase">Bir tane daha</acun:button>
    <acun:badge variant="info">{{ $quantity }} adet</acun:badge>
</div>
