<?php

namespace Acun\Ui\Tests\Feature;

use Acun\Ui\Tests\TestCase;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

/**
 * The markup side of acun:drawer's binding and options; the behaviour (focus,
 * scroll lock, Escape, backdrop, events, the binding itself) is tested in the
 * browser by tests/js/drawer.test.mjs.
 */
final class DrawerBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    public function test_wire_model_binds_the_drawer_state_on_its_root(): void
    {
        foreach (['', ' docked'] as $variant) {
            $root = $this->root(Blade::render('<acun:drawer name="detay"'.$variant.' title="Detay" wire:model.live="showDetail">İçerik</acun:drawer>'));

            self::assertSame('showDetail', $root->getAttribute('wire:model.live'), $variant);
            self::assertSame('model', $root->getAttribute('x-modelable'), $variant);
            self::assertStringStartsWith('acunDrawer(', $root->getAttribute('x-data'), $variant);
            self::assertSame('detay', $root->getAttribute('data-acun-drawer'), $variant);
        }
    }

    public function test_x_model_binds_it_too_and_an_unbound_drawer_is_not_modelable(): void
    {
        self::assertSame('model', $this->root(Blade::render('<acun:drawer name="d" x-model="detailOpen">İçerik</acun:drawer>'))->getAttribute('x-modelable'));
        self::assertFalse($this->root(Blade::render('<acun:drawer name="d">İçerik</acun:drawer>'))->hasAttribute('x-modelable'));
    }

    public function test_other_attributes_go_on_the_root(): void
    {
        $standard = $this->root(Blade::render('<acun:drawer name="d" class="z-60" data-test="panel" wire:key="detay">İçerik</acun:drawer>'));
        $docked = $this->root(Blade::render('<acun:drawer name="f" docked data-test="filtre">İçerik</acun:drawer>'));

        self::assertSame('relative z-50 z-60', $standard->getAttribute('class'));
        self::assertSame('panel', $standard->getAttribute('data-test'));
        self::assertSame('detay', $standard->getAttribute('wire:key'));
        self::assertSame('relative z-50 lg:z-40', $docked->getAttribute('class'));
        self::assertSame('filtre', $docked->getAttribute('data-test'));
    }

    public function test_the_backdrop_closes_it_unless_closable_on_backdrop_is_false(): void
    {
        foreach (['', ' docked'] as $variant) {
            $closable = $this->backdrop(Blade::render('<acun:drawer name="d"'.$variant.'>İçerik</acun:drawer>'));
            $kept = $this->backdrop(Blade::render('<acun:drawer name="d"'.$variant.' :closable-on-backdrop="false">İçerik</acun:drawer>'));

            self::assertSame('close()', $closable->getAttribute('x-on:click'), $variant);
            self::assertFalse($kept->hasAttribute('x-on:click'), $variant);
        }
    }

    public function test_clicks_in_the_panel_bubble_and_a_followed_link_closes_it(): void
    {
        $panel = (new DOMXPath($this->document(Blade::render('<acun:drawer name="d">İçerik</acun:drawer>'))))->query('//*[@x-ref="panel"]')->item(0);

        // No .stop: a dropdown inside the panel still hears clicks outside it.
        self::assertFalse($panel->hasAttribute('x-on:click.stop'));
        // followLink(): not a link the page handles itself, opens elsewhere or marks keep-open (tests/js/drawer.test.mjs).
        self::assertSame('followLink($event)', $panel->getAttribute('x-on:click'));
    }

    public function test_the_docked_panel_is_modal_only_as_the_mobile_overlay(): void
    {
        $panel = (new DOMXPath($this->document(Blade::render('<acun:drawer name="f" docked title="Filtreler">İçerik</acun:drawer>'))))->query('//*[@x-ref="panel"]')->item(0);

        self::assertFalse($panel->hasAttribute('aria-modal'));
        self::assertSame('overlay.toString()', $panel->getAttribute(':aria-modal'));
    }

    private function root(string $html): DOMElement
    {
        return (new DOMXPath($this->document($html)))->query('/html/body/*[1]')->item(0);
    }

    private function backdrop(string $html): DOMElement
    {
        return (new DOMXPath($this->document($html)))->query('/html/body/*[1]/div[@aria-hidden="true"]')->item(0);
    }

    private function document(string $html): \DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><html><body>'.$html.'</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }
}
