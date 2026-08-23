<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
  },
  className: {
    type: [String, Array, Object],
    default: '',
  },
});

const badgeClasses = computed(() => {
  const base = "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2";
  
  const variants = {
    default: "border-transparent bg-primary text-primary-foreground hover:bg-primary/80",
    secondary: "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
    destructive: "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
    outline: "text-foreground",
    success: "border-transparent bg-emerald-100 text-emerald-800 border-emerald-200",
    warning: "border-transparent bg-amber-100 text-amber-800 border-amber-200",
    info: "border-transparent bg-blue-100 text-blue-800 border-blue-200",
  };

  return cn(base, variants[props.variant] || variants.default, props.className);
});
</script>

<template>
  <div :class="badgeClasses">
    <slot />
  </div>
</template>
