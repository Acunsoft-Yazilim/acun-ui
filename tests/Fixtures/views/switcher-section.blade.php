{{-- A section a package adds to acun:theme-switcher (ThemeSwitcherTest): the switcher includes it with $section. --}}
<acun:theme-switcher.section :name="$section" title="Yoğunluk" description="Satır aralığı" :options="['rahat' => 'Rahat', 'sik' => ['label' => 'Sık', 'icon' => 'bars-3']]" value="$store.yogunluk.deger" change="$store.yogunluk.sec" />
