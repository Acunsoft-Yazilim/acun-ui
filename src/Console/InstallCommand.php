<?php

namespace Acun\Ui\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use JsonException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function Laravel\Prompts\confirm;

/**
 * php artisan acun:install [--check] [--example] [--npm | --no-npm] [--remove-font]
 *
 * Wires the free package into a Laravel application (https://ui.acunsoft.com/installation.html#free):
 *
 *   package.json            "@acunsoft/acun-ui": "file:vendor/acunsoft/acun-ui" in dependencies
 *   resources/css/app.css   @import 'tailwindcss'; @import '@acunsoft/acun-ui';
 *                           @source '../../vendor/acunsoft/acun-ui/resources/views';
 *   resources/js/app.js     import '@acunsoft/acun-ui';
 *
 * The kit draws its own form controls (2.2): the @tailwindcss/forms plugin is neither added nor checked;
 * an application that has it (2.1 added it) keeps it, which does no harm.
 *
 * The starter kit's entries (resources/css/acun-ui.css, resources/js/acun-ui.js) are edited instead of
 * app.css and app.js when they exist. With acunsoft/acun-pro installed it adds that package's lines
 * too (into the entries that import Acun Pro when that is not the main one, see files()) and lists
 * the steps left (Vite, layout, menu, first page). Only missing lines are added (see
 * Wiring), so a second run changes nothing; a missing file is never created. --example copies an
 * example page, --npm runs the package manager's install and build (asked when interactive).
 * Laravel's skeleton sets its own font (--font-sans in an @theme block of app.css), which replaces the
 * kit's: an interactive run asks to remove it (yes by default), --remove-font removes it without asking,
 * and a run without interaction leaves it with a warning (a script never changes the design by itself).
 *
 * --check changes nothing: it lists every item with a fix for the missing ones, and exits 1 when
 * something is missing (for support).
 */
final class InstallCommand extends Command
{
    protected $signature = 'acun:install
        {--check : Change nothing: list what is set up and what is missing (exit code 1 when something is)}
        {--example : Copy an example page to resources/views/acun/example.blade.php}
        {--npm : Run the package manager\'s install and build at the end}
        {--no-npm : Do not run the package manager}
        {--remove-font : Remove the --font-sans line of an @theme block (Laravel\'s own font) without asking}';

    protected $description = 'Wire Acun UI into package.json and the CSS and JS entries (Acun Pro\'s lines too when it is installed)';

    private const GUIDE = 'https://ui.acunsoft.com/installation.html#free';

    private const PRO_GUIDE = 'https://ui.acunsoft.com/installation.html#existing';

    /** The same lines written by hand. */
    private const MANUAL = 'https://ui.acunsoft.com/installation.html#manual';

    /** The example page (--example) and the route that shows it. */
    private const EXAMPLE = 'views/acun/example.blade.php';

    private const EXAMPLE_ROUTE = "Route::view('/acun-example', 'acun.example');";

    /** What acunsoft/acun-pro still needs by hand after the wiring (installation guide, #existing). */
    private const PRO_STEPS = [
        'vite.config.js: the fonts (Public Sans, DM Mono) and woff2Only()',
        'The layout: resources/views/layouts/admin/vertical.blade.php',
        'The menu: resources/menu/main.json',
        'The first page and its route in routes/web.php',
    ];

    public function handle(): int
    {
        if ($this->option('npm') && $this->option('no-npm')) {
            $this->components->error('Pass --npm or --no-npm, not both.');

            return self::FAILURE;
        }

        $pro = is_dir(base_path('vendor/acunsoft/acun-pro'));

        return $this->option('check') ? $this->check($pro) : $this->install($pro);
    }

    private function install(bool $pro): int
    {
        $this->newLine();
        $changed = [];
        $warnings = [];
        $complete = true;
        $proAdded = false;

        foreach ($this->files($pro) as $path => $edit) {
            $name = $this->display($path);

            if (! is_file($path)) {
                $this->components->twoColumnDetail($name, '<fg=yellow>not found</>');
                $warnings[] = "{$name} was not found: nothing was created. ".($name === 'package.json'
                    ? 'Create it (npm init -y), then run php artisan acun:install again.'
                    : 'Create it with the lines of the guide: '.self::MANUAL);
                $complete = false;

                continue;
            }

            $original = (string) file_get_contents($path);

            try {
                $result = $edit($original);
            } catch (JsonException|UnexpectedValueException|RuntimeException $exception) {
                $this->components->twoColumnDetail($name, '<fg=red>not changed</>');
                $warnings[] = "{$name}: ".($exception instanceof JsonException ? 'not valid JSON ('.$exception->getMessage().')' : $exception->getMessage()).' Nothing was changed in it.';
                $complete = false;

                continue;
            }

            $added = array_values(array_filter($result['items'], fn (array $item): bool => $item['added']));

            // Laravel's own font (--font-sans in @theme) replaces the kit's: asked, or --remove-font.
            $fontRemoved = [];
            $fontLine = $result['fontSans'] ?? null;
            $fontAnswer = $fontLine !== null ? $this->removeFont($name, $fontLine) : null;

            if ($fontAnswer === true) {
                $removal = Wiring::removeFontSans($result['content']);
                $result['content'] = $removal['content'];
                $fontRemoved = $removal['removed'];
            }

            if ($result['content'] !== $original) {
                file_put_contents($path, $result['content']);
                $changed[] = $name;
            }

            $this->components->twoColumnDetail($name, $added === [] && $fontRemoved === [] ? '<fg=gray>unchanged</>' : '<fg=green>updated</>');

            foreach ($added as $item) {
                $this->line("    <fg=green>+</> {$item['line']}");
                $proAdded = $proAdded || $item['pro'];
            }

            foreach ($fontRemoved as $line) {
                $this->line("    <fg=red>-</> {$line}");
            }

            foreach ($result['items'] as $item) {
                if (($item['found'] ?? null) !== null) {
                    $warnings[] = "{$name}: \"{$item['package']}\" is \"{$item['found']}\", left as it is. In a Laravel application it comes"
                        ." from the Composer copy, \"{$item['version']}\", so the CSS and JS match the Blade components: change it, then run npm install.";
                }
            }

            // Left without interaction: a warning. Kept on purpose (answered no): nothing to say.
            if ($fontAnswer === null && $fontLine !== null) {
                $warnings[] = $this->fontSansWarning($name, $fontLine);
            }
        }

        foreach ($warnings as $warning) {
            $this->components->warn($warning);
        }

        // Asked only while the page is not there yet.
        $example = $this->option('example') || ($this->input->isInteractive() && ! is_file(resource_path(self::EXAMPLE))
            && confirm('Copy an example page to resources/'.self::EXAMPLE.'?', default: false));

        if ($example && $this->copyExample()) {
            $changed[] = 'resources/'.self::EXAMPLE;
        }

        if ($warnings === [] || $example) {
            $this->newLine();
        }

        if ($changed !== []) {
            $this->components->info('Changed: '.implode(', ', $changed).'.');
        } elseif ($complete) {
            $this->components->info('Already set up. Nothing changed.');
        } else {
            $this->components->warn('Nothing changed.');
        }

        if ($pro) {
            if ($proAdded) {
                $this->line('  <options=bold>Acun Pro</>: the rest is by hand, see '.self::PRO_GUIDE);
                $this->components->bulletList(self::PRO_STEPS);
                $this->newLine();
            } else {
                $this->line('  Acun Pro: Vite, the layout, the menu and the first page are set by hand: '.self::PRO_GUIDE);
                $this->newLine();
            }
        }

        $manager = $this->packageManager();
        $ran = false;

        if ($this->shouldRunPackageManager($manager, $changed !== [])) {
            if (! $this->runPackageManager($manager)) {
                return self::FAILURE;
            }

            $ran = true;
        } elseif ($this->option('npm')) {
            $this->components->error('package.json was not found: '.$manager.' did not run.');

            return self::FAILURE;
        }

        if ($changed !== [] || $ran) {
            $this->nextSteps($pro, $example, $ran, $manager);
        }

        return self::SUCCESS;
    }

    /**
     * The files to wire and their edit: package.json, the CSS entry, the JS entry.
     *
     * With acunsoft/acun-pro, its lines go into the entry too, unless the entry does not import Acun Pro
     * and other entries of the folder do (an application with one Vite entry per section, like the demos'
     * resources/css/panel.css next to app.css): those entries then get every line, the entry the free ones.
     *
     * @return array<string, \Closure(string): array>
     */
    private function files(bool $pro): array
    {
        $files = [base_path('package.json') => fn (string $content): array => Wiring::packageJson($content, $pro)];

        foreach (['css', 'js'] as $type) {
            $entry = $this->entry($type);
            $proEntries = $pro ? $this->proEntries($type, $entry) : [];
            $edit = fn (bool $withPro): \Closure => $type === 'css'
                ? fn (string $content): array => Wiring::css($content, $withPro)
                : fn (string $content): array => Wiring::js($content, $withPro);

            $files[$entry] = $edit($pro && $proEntries === []);

            foreach ($proEntries as $proEntry) {
                $files[$proEntry] = $edit(true);
            }
        }

        return $files;
    }

    /**
     * The other resources/{css,js}/*.{css,js} files that import Acun Pro, when the entry does not.
     *
     * @return list<string>
     */
    private function proEntries(string $type, string $entry): array
    {
        if (is_file($entry) && Wiring::importsPro($type, (string) file_get_contents($entry))) {
            return [];
        }

        $files = glob(resource_path("{$type}/*.{$type}")) ?: [];
        sort($files);

        return array_values(array_filter($files, fn (string $file): bool => $file !== $entry
            && Wiring::importsPro($type, (string) file_get_contents($file))));
    }

    /** resources/css/app.css and resources/js/app.js, or the starter kit's acun-ui.css and acun-ui.js. */
    private function entry(string $type): string
    {
        $starterKit = resource_path("{$type}/acun-ui.{$type}");

        return is_file($starterKit) ? $starterKit : resource_path("{$type}/app.{$type}");
    }

    private function copyExample(): bool
    {
        $path = resource_path(self::EXAMPLE);
        $name = $this->display($path);

        if (is_file($path)) {
            $this->components->twoColumnDetail($name, '<fg=gray>exists, kept</>');

            return false;
        }

        // The page's texts in Turkish when the application is (the rest of the package speaks English).
        $stub = str_starts_with(app()->getLocale(), 'tr') ? 'example.tr.blade.php.stub' : 'example.blade.php.stub';
        $entries = array_map(fn (string $entry): string => "'".$this->display($entry)."'", [$this->entry('css'), $this->entry('js')]);
        $content = str_replace('{{ vite }}', '@vite(['.implode(', ', $entries).'])', (string) file_get_contents(dirname(__DIR__, 2).'/stubs/'.$stub));

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
        $this->components->twoColumnDetail($name, '<fg=green>created</>');

        return true;
    }

    /** npm, pnpm, yarn or bun, by the lock file (npm without one). */
    private function packageManager(): string
    {
        return match (true) {
            is_file(base_path('pnpm-lock.yaml')) => 'pnpm',
            is_file(base_path('yarn.lock')) => 'yarn',
            is_file(base_path('bun.lock')), is_file(base_path('bun.lockb')) => 'bun',
            default => 'npm',
        };
    }

    /** --npm, --no-npm; else, when interactive and something needs it, the answer to a question. */
    private function shouldRunPackageManager(string $manager, bool $changed): bool
    {
        if ($this->option('no-npm') || ! is_file(base_path('package.json'))) {
            return false;
        }

        if ($this->option('npm')) {
            return true;
        }

        $needed = $changed || ! $this->linked('acun-ui') || $this->build() === null;

        return $needed && $this->input->isInteractive()
            && confirm("Run {$manager} install and {$manager} run build now?", default: true);
    }

    /** Runs install, then build (when package.json has the script), with their output; false when one fails. */
    private function runPackageManager(string $manager): bool
    {
        $scripts = json_decode((string) file_get_contents(base_path('package.json')), true)['scripts'] ?? [];
        $commands = ["{$manager} install", ...(isset($scripts['build']) ? ["{$manager} run build"] : [])];

        foreach ($commands as $command) {
            $this->newLine();
            $this->line("  <fg=gray>\$ {$command}</>");

            try {
                $result = Process::path(base_path())->forever()->run($command, function (string $type, string $output): void {
                    $this->output->write($output);
                });
            } catch (Throwable $exception) {
                $this->components->error("{$command} could not start: {$exception->getMessage()}");

                return false;
            }

            if ($result->failed()) {
                $hint = $result->exitCode() === 127 ? " Is {$manager} installed and on the PATH?" : ' Fix the error above, then run it again.';
                $this->components->error("{$command} failed (exit code {$result->exitCode()}).{$hint}");

                return false;
            }
        }

        if (! isset($scripts['build'])) {
            $this->components->warn('package.json has no "build" script: nothing was built.');
        }

        return true;
    }

    private function nextSteps(bool $pro, bool $example, bool $ran, string $manager): void
    {
        $vite = '@vite(['.implode(', ', array_map(fn (string $entry): string => "'".$this->display($entry)."'", [$this->entry('css'), $this->entry('js')])).'])';
        $steps = [];

        if ($example) {
            $steps[] = 'Add the example page\'s route to routes/web.php, then open /acun-example: '.self::EXAMPLE_ROUTE;
        } elseif (! $pro) {
            $steps[] = "Use <acun:layout.blank> on a page (or <acun:layout.head> in your own layout), with {$vite} in its head";
        }

        $steps[] = $ran ? "Run {$manager} run dev while you work" : "Run {$manager} install, then {$manager} run dev";
        $steps[] = 'Guide: '.($pro ? self::PRO_GUIDE : self::GUIDE);

        if ($ran) {
            $this->newLine();
        }

        $this->line('  <options=bold>Next</>');
        $this->components->bulletList($steps);
    }

    private function check(bool $pro): int
    {
        $manager = $this->packageManager();
        /** @var list<array{0: ?bool, 1: string, 2?: string}> $rows true ✓, false ✗ (with the fix), null ! (a note) */
        $rows = [];

        $rows[] = is_dir(base_path('vendor/acunsoft/acun-ui'))
            ? [true, 'vendor/acunsoft/acun-ui is installed']
            : [false, 'vendor/acunsoft/acun-ui is missing', 'Run composer install (npm takes the CSS and JS from there).'];

        if ($pro) {
            $rows[] = [true, 'vendor/acunsoft/acun-pro is installed (its lines are checked too)'];
        }

        foreach ($this->files($pro) as $path => $edit) {
            $name = $this->display($path);

            if (! is_file($path)) {
                $rows[] = [false, "{$name} is missing", $name === 'package.json'
                    ? 'Create it (npm init -y), then run php artisan acun:install.'
                    : 'Create it with the lines of the guide: '.self::MANUAL];

                continue;
            }

            try {
                $result = $edit($content = (string) file_get_contents($path));
            } catch (JsonException|UnexpectedValueException|RuntimeException $exception) {
                $rows[] = [false, "{$name} cannot be read: ".($exception instanceof JsonException ? 'not valid JSON' : $exception->getMessage()), "Fix {$name}, then run php artisan acun:install."];

                continue;
            }

            foreach ($result['items'] as $item) {
                $rows[] = match (true) {
                    $item['added'] => [false, "{$name}: {$item['line']}", 'Missing: php artisan acun:install adds it'.($name === 'package.json' ? ", then run {$manager} install." : '.')],
                    ($item['found'] ?? null) !== null => [false, "{$name}: \"{$item['package']}\" is \"{$item['found']}\"", "Set it to \"{$item['version']}\" so the CSS and JS match the Blade components, then run {$manager} install."],
                    default => [true, "{$name}: {$item['line']}"],
                };
            }

            if (array_key_exists('fontSans', $result) && ($line = Wiring::fontSans($content)) !== null) {
                $rows[] = [null, "{$name} line {$line}: an @theme block sets --font-sans", 'It replaces Acun UI\'s fonts (Public Sans, DM Mono). Remove that line unless you want your own font.'];
            }
        }

        $build = $this->build();

        // Built assets without node_modules (the starter kit and the demos of the Acun Pro zip, a server
        // without Node.js): the pages work, Node.js is only needed to change the design.
        if ($build === 'build' && ! is_dir(base_path('node_modules'))) {
            $rows[] = [null, 'node_modules is missing: the pages use the built assets in public/build', "Node.js is only needed to change the design: run {$manager} install, then {$manager} run build."];
        } else {
            foreach ($pro ? ['acun-ui', 'acun-pro'] : ['acun-ui'] as $package) {
                $rows[] = match ($this->linked($package)) {
                    'link' => [true, "node_modules/@acunsoft/{$package} points to vendor/acunsoft/{$package}"],
                    'copy' => [true, "node_modules/@acunsoft/{$package} is a copy of vendor/acunsoft/{$package} (run {$manager} install again after composer update)"],
                    default => [false, "node_modules/@acunsoft/{$package} does not point to vendor/acunsoft/{$package}", "Run {$manager} install (after composer install: npm takes the package from vendor/)."],
                };
            }
        }

        $rows[] = $build !== null
            ? [true, $build === 'hot' ? 'public/hot: the Vite dev server is running' : 'public/build/manifest.json: the assets are built']
            : [false, 'No build: public/build/manifest.json and public/hot are missing', "Run {$manager} run build (or {$manager} run dev while you work)."];

        $this->newLine();

        foreach ($rows as $row) {
            $mark = match ($row[0]) {
                true => '<fg=green>✓</>',
                false => '<fg=red>✗</>',
                null => '<fg=yellow>!</>',
            };
            $this->line("  {$mark} {$row[1]}");

            if (isset($row[2])) {
                $this->line("    <fg=gray>→ {$row[2]}</>");
            }
        }

        $problems = count(array_filter($rows, fn (array $row): bool => $row[0] === false));
        $this->newLine();

        if ($problems > 0) {
            $this->components->error($problems === 1 ? '1 thing is missing: fix it as shown above.' : "{$problems} things are missing: fix them as shown above.");
            $this->line('  Guide: '.($pro ? self::PRO_GUIDE : self::GUIDE));
            $this->newLine();

            return self::FAILURE;
        }

        $this->components->info('Acun UI is set up.');

        return self::SUCCESS;
    }

    /** "link" when node_modules/@acunsoft/{package} is the vendor/ folder, "copy" for a copy of its version, else null. */
    private function linked(string $package): ?string
    {
        $vendor = realpath(base_path("vendor/acunsoft/{$package}"));
        $module = realpath(base_path("node_modules/@acunsoft/{$package}"));

        if ($vendor === false || $module === false) {
            return null;
        }

        if ($vendor === $module) {
            return 'link';
        }

        $version = fn (string $folder): ?string => json_decode((string) @file_get_contents($folder.'/package.json'), true)['version'] ?? null;

        return $version($vendor) !== null && $version($vendor) === $version($module) ? 'copy' : null;
    }

    /** "hot" while the Vite dev server runs, "build" when the assets are built, else null. */
    private function build(): ?string
    {
        return match (true) {
            is_file(public_path('hot')) => 'hot',
            is_file(public_path('build/manifest.json')) => 'build',
            default => null,
        };
    }

    private function fontSansWarning(string $file, int $line): string
    {
        return "{$file} line {$line}: an @theme block sets --font-sans, which replaces Acun UI's fonts (Public Sans, DM Mono)."
            .' Remove that line (the whole block if nothing else is in it) unless you want your own font,'
            .' or run php artisan acun:install --remove-font. Without interaction acun:install leaves it as it is.';
    }

    /**
     * Whether to remove Laravel's own font: true with --remove-font or a yes; false for a no (the
     * application keeps its font on purpose); null without interaction (left, with a warning).
     */
    private function removeFont(string $file, int $line): ?bool
    {
        if ($this->option('remove-font')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return null;
        }

        return confirm(
            "{$file} line {$line}: Laravel's own font (--font-sans in @theme) replaces Acun UI's fonts (Public Sans, DM Mono). Remove it?",
            default: true,
            hint: 'Answer no to keep your own font: the components then use it.',
        );
    }

    /** A path relative to the application root. */
    private function display(string $path): string
    {
        $base = rtrim(base_path(), '/\\').DIRECTORY_SEPARATOR;

        return str_replace(DIRECTORY_SEPARATOR, '/', str_starts_with($path, $base) ? substr($path, strlen($base)) : $path);
    }
}
