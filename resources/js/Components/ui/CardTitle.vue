<script setup>
import { cn } from '@/lib/utils';

defineProps({
  className: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <h3 :class="cn('text-lg font-bold leading-none tracking-tight text-slate-900', className)">
    <slot />
  </h3>
</template>
