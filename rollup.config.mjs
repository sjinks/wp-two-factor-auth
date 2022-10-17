import { resolve } from 'path';
import { terser } from '@wwa/rollup-plugin-terser';
import postcss from 'rollup-plugin-postcss';

/**
 * @type {import('rollup').RollupOptions[]}
 */
const config = [
    {
        input: {
            profile: 'assets-dev/profile.js',
            tfa: 'assets-dev/tfa.js',
            'user-settings': 'assets-dev/user-settings.js',
        },
        output: {
            dir: 'assets',
            preserveModules: true,
            entryFileNames: '[name].min.js',
            format: 'esm',
            plugins: [
                terser(),
            ],
            compact: true,
            sourcemap: 'hidden',
            strict: false,
        },
        strictDeprecations: true,
    },
    {
        input: 'assets-dev/admin.css',
        output: {
            dir: 'assets',
        },
        plugins: [
            postcss({
                modules: false,
                extract: resolve('assets/admin.min.css'),
                minimize: true,
            }),
            (() => ({
                name: 'clean-up-unused-js-files',
                generateBundle(options, bundle, isWrite) {
                    delete bundle['admin.js'];
                },
            }))(),
        ],
    }
];

export default config;
