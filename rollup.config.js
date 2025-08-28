import { nodeResolve } from '@rollup/plugin-node-resolve';
import { babel } from '@rollup/plugin-babel';

import terser from '@rollup/plugin-terser'

export default {
  input: 'src/meting.js',
  output: [
    {
      file: 'lib/meting.esm.js',
      format: 'es'
    },
    {
      file: 'lib/meting.js',
      format: 'cjs',
      exports: 'default'
    }
  ],
  plugins: [
    nodeResolve({
      preferBuiltins: true
    }),
    babel({ babelHelpers: 'bundled' }),
    // terser()
  ],
  external: ['crypto', 'url']
};