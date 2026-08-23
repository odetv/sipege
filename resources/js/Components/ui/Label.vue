<script setup>
import { cn } from '@/lib/utils';

defineProps({
  for: {
    type: String,
    default: '',
  },
  className: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  }
});
</script>

<template>
  <label
    :for="for"
    :class="cn(
      'text-sm font-semibold leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-slate-800 inline-flex items-center gap-1',
      className
    )"
  >
    <slot />
    <span v-if="required" class="text-destructive font-bold text-xs ml-0.5">*</span>
  </label>
</template>
