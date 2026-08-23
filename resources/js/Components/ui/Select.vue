<script setup>
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Pilih opsi...',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  className: {
    type: [String, Array, Object],
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  }
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="relative w-full">
    <select
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      @change="$emit('update:modelValue', $event.target.value)"
      :class="cn(
        'flex h-10 w-full appearance-none rounded-md border border-input bg-background px-3 py-2 pr-8 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-150 cursor-pointer',
        className
      )"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <slot />
    </select>
    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500">
      <ChevronDown class="h-4 w-4" />
    </div>
  </div>
</template>
