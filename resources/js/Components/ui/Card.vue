<script setup>
import { cn } from '@/lib/utils';

defineProps({
  className: {
    type: [String, Array, Object],
    default: '',
  },
});
</script>

<template>
  <div
    :class="cn(
      'rounded-xl border border-slate-200 bg-card text-card-foreground shadow-sm hover:shadow-md transition-shadow duration-200',
      className
    )"
  >
    <slot />
  </div>
</template>
