<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Assets\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Scaffolds build tool configuration files for Vite and/or Webpack.
 *
 * Usage:
 *   yii blackcube:assets/init
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
#[AsCommand(
    name: 'blackcube:assets/init',
    description: 'Initialize Vite/Webpack build configuration'
)]
class AssetsController extends Command
{
    private const BUILDER_VITE = 'vite';
    private const BUILDER_WEBPACK = 'webpack';

    public function __construct(
        private readonly Aliases $aliases,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $questionHelper = $this->getHelper('question');

        $io->title('Blackcube Assets Initialization');

        $builderQuestion = new ChoiceQuestion(
            'Which builder(s) do you want to use?',
            [
                self::BUILDER_VITE,
                self::BUILDER_WEBPACK,
                'both',
            ],
            'both'
        );
        $builderChoice = $questionHelper->ask($input, $output, $builderQuestion);

        $builders = $builderChoice === 'both'
            ? [
                self::BUILDER_VITE,
                self::BUILDER_WEBPACK,
            ]
            : [$builderChoice];

        $sourceQuestion = new Question('Source directory (relative to project root): ', 'assets/src');
        $sourceDir = $questionHelper->ask($input, $output, $sourceQuestion);

        $outputQuestion = new Question('Output base directory (relative to project root): ', 'assets');
        $outputBaseDir = $questionHelper->ask($input, $output, $outputQuestion);

        $rootPath = $this->aliases->get('@root');

        $this->generateAssetsConfig($rootPath, $builders, $sourceDir, $outputBaseDir, $io);
        $this->generatePackageJson($rootPath, $builders, $io);
        $this->generateTsConfig($rootPath, $io);
        $this->generateSourceTsConfig($rootPath, $sourceDir, $io);

        if (in_array(self::BUILDER_VITE, $builders, true) === true) {
            $this->generateViteFiles($rootPath, $io);
        }

        if (in_array(self::BUILDER_WEBPACK, $builders, true) === true) {
            $this->generateWebpackFiles($rootPath, $io);
        }

        $io->success('Build configuration generated!');
        $io->text([
            'Next steps:',
            '  1. Run: npm install',
            '  2. Create your source files in: ' . $sourceDir,
            '  3. Build with: npm run dist-clean',
        ]);

        return ExitCode::OK;
    }

    private function generateAssetsConfig(
        string $rootPath,
        array $builders,
        string $sourceDir,
        string $outputBaseDir,
        SymfonyStyle $io
    ): void {
        $config = [
            'builders' => $builders,
            'sourceDir' => $sourceDir,
            'outputBaseDir' => $outputBaseDir,
            'entry' => [
                'app' => 'js/app.ts',
            ],
            'subDirectories' => [
                'js' => 'js',
                'css' => 'css',
            ],
            'catalog' => 'assets-catalog.json',
        ];

        $path = $rootPath . '/assets-blackcube.json';
        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $io->text('Created: assets-blackcube.json');
    }

    private function generatePackageJson(string $rootPath, array $builders, SymfonyStyle $io): void
    {
        $path = $rootPath . '/package.json';
        $exists = file_exists($path) === true;

        $package = $exists
            ? json_decode(file_get_contents($path), true)
            : [
                'name' => 'app-assets',
                'private' => true,
                'type' => 'module',
            ];

        $scripts = $package['scripts'] ?? [];

        if (in_array(self::BUILDER_VITE, $builders, true) === true) {
            $scripts['dist-clean-vite'] = 'vite build';
        }

        if (in_array(self::BUILDER_WEBPACK, $builders, true) === true) {
            $scripts['dist-clean-webpack'] = 'webpack --mode production';
            $scripts['watch'] = 'webpack --mode development --watch';
        }

        $distCleanParts = [];
        if (in_array(self::BUILDER_VITE, $builders, true) === true) {
            $distCleanParts[] = 'npm run dist-clean-vite';
        }
        if (in_array(self::BUILDER_WEBPACK, $builders, true) === true) {
            $distCleanParts[] = 'npm run dist-clean-webpack';
        }
        $scripts['dist-clean'] = implode(' && ', $distCleanParts);

        $package['scripts'] = $scripts;

        $devDeps = $package['devDependencies'] ?? [];

        $devDeps['typescript'] = '^5.0';
        $devDeps['ts-node'] = '^10.0';
        $devDeps['@types/node'] = '^22.0';

        if (in_array(self::BUILDER_VITE, $builders, true) === true) {
            $devDeps['vite'] = '^6.0';
            $devDeps['sass'] = '^1.80';
        }

        if (in_array(self::BUILDER_WEBPACK, $builders, true) === true) {
            $devDeps['webpack'] = '^5.0';
            $devDeps['webpack-cli'] = '^6.0';
            $devDeps['assets-webpack-plugin'] = '^7.0';
            $devDeps['compression-webpack-plugin'] = '^11.0';
            $devDeps['mini-css-extract-plugin'] = '^2.0';
            $devDeps['ts-loader'] = '^9.0';
            $devDeps['css-loader'] = '^7.0';
            $devDeps['sass-loader'] = '^16.0';
            $devDeps['postcss-loader'] = '^8.0';
            $devDeps['source-map-loader'] = '^5.0';
            $devDeps['sass'] = '^1.80';
        }

        ksort($devDeps);
        $package['devDependencies'] = $devDeps;

        file_put_contents($path, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $io->text($exists ? 'Updated: package.json' : 'Created: package.json');
    }

    private function generateTsConfig(string $rootPath, SymfonyStyle $io): void
    {
        $content = $this->loadTemplate('tsconfig.json');
        $path = $rootPath . '/tsconfig.json';
        file_put_contents($path, $content);
        $io->text('Created: tsconfig.json');
    }

    private function generateSourceTsConfig(string $rootPath, string $sourceDir, SymfonyStyle $io): void
    {
        $sourcePath = $rootPath . '/' . $sourceDir;

        if (is_dir($sourcePath) === false) {
            mkdir($sourcePath, 0755, true);
        }

        $content = $this->loadTemplate('tsconfig.source.json');
        $path = $sourcePath . '/tsconfig.json';
        file_put_contents($path, $content);
        $io->text('Created: ' . $sourceDir . '/tsconfig.json');
    }

    private function generateViteFiles(string $rootPath, SymfonyStyle $io): void
    {
        $viteConfig = $this->loadTemplate('vite.config.mts');
        file_put_contents($rootPath . '/vite.config.mts', $viteConfig);
        $io->text('Created: vite.config.mts');

        $plugin = $this->loadTemplate('vite-manifest-plugin.mts');
        file_put_contents($rootPath . '/vite-manifest-plugin.mts', $plugin);
        $io->text('Created: vite-manifest-plugin.mts');
    }

    private function generateWebpackFiles(string $rootPath, SymfonyStyle $io): void
    {
        $config = $this->loadTemplate('webpack.config.mts');
        file_put_contents($rootPath . '/webpack.config.mts', $config);
        $io->text('Created: webpack.config.mts');
    }

    private function loadTemplate(string $name): string
    {
        $templatePath = dirname(__DIR__, 2) . '/resources/templates/' . $name;
        return file_get_contents($templatePath);
    }
}
