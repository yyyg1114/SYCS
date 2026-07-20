<template>
  <nav class="fixed bottom-0 left-0 right-0 h-16 bg-[#0b0f19]/90 backdrop-blur-md border-t border-slate-800 flex items-center justify-around px-4 z-50">
    <!-- ホームボタンの例 -->
    <button class="flex flex-col items-center gap-1 text-indigo-400">
      <Icon name="lucide:home" class="w-6 h-6" />
      <span class="text-[10px] font-bold">ホーム</span>
    </button>
    
    <!-- 他のボタンもここに追加していく -->
    <button class="flex flex-col items-center gap-1 text-slate-500">
      <Icon name="lucide:menu" class="w-6 h-6" />
      <span class="text-[10px]">メニュー</span>
    </button>
  </nav>
</template>

<script setup>
// ここにボトムナビのロジック（メニューの開閉など）を書いていくよ
</script>