// The free components with the states their markup carries, rendered by the kit (render.php): the
// sample page of the CSS parity test (css-parity.test.mjs) and the HTML example (npm run build:example).
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../../../../', import.meta.url));

export const SAMPLES = {
    buttons: `<div>
        <acun:button>Kaydet</acun:button><acun:button variant="secondary">İptal</acun:button><acun:button variant="success">Onayla</acun:button>
        <acun:button variant="danger">Sil</acun:button><acun:button variant="warning">Uyar</acun:button><acun:button variant="info">Bilgi</acun:button>
        <acun:button variant="light">Açık</acun:button><acun:button variant="dark">Koyu</acun:button><acun:button variant="outline">Çerçeve</acun:button>
        <acun:button variant="soft-success">Yumuşak</acun:button><acun:button variant="white">Beyaz</acun:button><acun:button variant="ghost">Hayalet</acun:button>
        <acun:button variant="link">Bağlantı</acun:button><acun:button size="sm">Küçük</acun:button><acun:button size="lg" shadow>Büyük</acun:button>
        <acun:button disabled>Pasif</acun:button><acun:button loading>Yükleniyor</acun:button><acun:button badge="3">Sepet</acun:button>
        <acun:button href="/siparisler" external>Siparişler</acun:button>
    </div>`,
    badges: `<div><acun:badge>Yeni</acun:badge><acun:badge variant="success">Ödendi</acun:badge><acun:badge variant="danger">İptal</acun:badge><acun:badge variant="warning">Bekliyor</acun:badge><acun:badge variant="info">Kargoda</acun:badge><acun:kbd>⌘K</acun:kbd></div>`,
    alerts: `<div><acun:alert variant="info" title="Bilgi">Sipariş kargoya verildi.</acun:alert><acun:alert variant="danger" dismissible>Ödeme alınamadı.</acun:alert></div>`,
    card: `<acun:card title="Bu ayki satışlar" subtitle="1–25 Ekim"><acun:stat-card label="Ciro" value="₺248.300" /><acun:progress :value="64" /><acun:skeleton class="h-4" /><acun:spinner /></acun:card>`,
    people: `<div><acun:avatar name="Ayşe Yılmaz" /><acun:avatar-group><acun:avatar name="Can Öztürk" /><acun:avatar name="Elif Şahin" /></acun:avatar-group><acun:empty-state title="Sipariş yok" description="İlk siparişiniz geldiğinde burada görünür." /></div>`,
    fields: `<div>
        <acun:input name="musteri" label="Müşteri" placeholder="Ad soyad" />
        <acun:input name="eposta" type="email" label="E-posta" hint="Faturayı bu adrese göndeririz." />
        <acun:input name="kod" label="Kupon" disabled value="EKIM10" />
        <acun:select name="durum" label="Durum" :options="['paid' => 'Ödendi', 'new' => 'Yeni']" />
        <acun:textarea name="not" label="Not" />
        <acun:checkbox name="onay" label="Faturayı e-posta ile gönder" checked />
        <acun:radio name="kargo" value="hizli" label="Hızlı kargo" checked />
        <acun:switch name="aktif" label="Ürün satışta" checked />
        <acun:input-group name="fiyat" label="Fiyat" prefix="₺" />
    </div>`,
    table: `<acun:table><table class="ui-table"><thead><tr><th>Sipariş</th><th>Tutar</th></tr></thead><tbody><tr><td>SP-1048</td><td>₺2.499,00</td></tr></tbody></table></acun:table>`,
    lists: `<div><acun:description-list><acun:description-list.item label="Müşteri">Ayşe Yılmaz</acun:description-list.item></acun:description-list><acun:list-group><acun:list-group.item>Kablosuz kulaklık</acun:list-group.item></acun:list-group><acun:breadcrumb :items="[['label' => 'Siparişler', 'href' => '/siparisler'], ['label' => 'SP-1048']]" /><acun:divider /></div>`,
};

/** Blade templates ({ key: template }) rendered by the kit, as { key: html }. */
export function renderBlade(templates) {
    return JSON.parse(execFileSync('php', [`${root}packages/acun-ui/tests/js/render.php`], { input: JSON.stringify(templates), maxBuffer: 64 * 1024 * 1024 }).toString());
}

/** The Blade samples rendered by the kit (render.php), joined into one page body. */
export function renderSamples() {
    const html = renderBlade(SAMPLES);

    return Object.entries(html).map(([key, markup]) => `<section data-sample="${key}">${markup}</section>`).join('\n');
}

