<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
await navigateTo('/home', { replace: true })
</script>
<template><div /></template>
