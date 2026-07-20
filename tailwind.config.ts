import type { Config } from 'tailwindcss'

export default <Partial<Config>>{
  darkMode: 'class', // ダークモード設定をクラス制御（HTML要素のclass="dark"）に連動
  content: [
    `./components/**/*.{vue,js,ts}`,
    `./layouts/**/*.vue`,
    `./pages/**/*.vue`,
    `./plugins/**/*.{js,ts}`,
    `./utils/**/*.{js,ts}`,
    `./App.{js,ts,vue}`,
    `./app.{js,ts,vue}`,
    `./Error.{js,ts,vue}`,
    `./error.{js,ts,vue}`
  ],
  theme: {
    extend: {
      // SYCS独自の美しいフォントファミリー設定
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Noto Sans JP', 'sans-serif'],
      },
      // SYCS専用のモダンなプレミアムカラーパレット定義
      colors: {
        brand: {
          50: '#f5f3ff',
          100: '#edd1ff',
          500: '#a855f7', // 動画にあったメインの鮮やかなパープル
          600: '#9333ea',
          700: '#7e22ce',
        }
      }
    },
  },
  plugins: [],
}