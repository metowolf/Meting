import { nodeResolve } from '@rollup/plugin-node-resolve';
import { babel } from '@rollup/plugin-babel';
import { readFileSync } from 'fs';
import terser from '@rollup/plugin-terser'

// 读取 package.json 中的版本号
const packageInfo = JSON.parse(readFileSync('./package.json', 'utf8'));

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
      exports: 'auto'
    }
  ],
  plugins: [
    nodeResolve({
      preferBuiltins: true
    }),
    babel({ babelHelpers: 'bundled' }),
    // 版本号注入插件
    {
      name: 'inject-version',
      transform(code, id) {
        if (id.endsWith('src/meting.js')) {
          return code.replace('__VERSION__', packageInfo.version);
        }
        return null;
      }
    },
    terser({
      format: {
        comments: false
      },
      compress: {
        drop_console: false
      }
    })
  ],
  external: ['crypto', 'url', 'fs', 'path']
};