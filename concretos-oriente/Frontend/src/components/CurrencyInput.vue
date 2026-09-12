<template>
  <input ref="inputRef" type="text" @input="$emit('update:modelValue', numberValue)" />
</template>

<script setup>
import { useCurrencyInput } from 'vue-currency-input'
import { watch } from 'vue'

const props = defineProps({
  modelValue: Number,
  options: Object
})

const emit = defineEmits(['update:modelValue'])

const { inputRef, setValue, numberValue } = useCurrencyInput({
  currency: 'GTQ',
  hideCurrencySymbolOnFocus: true,
  hideGroupingSeparatorOnFocus: false,
  hideNegligibleDecimalDigitsOnFocus: false,
  autoDecimalDigits: false,
  useGrouping: true,
  accountingSign: false,
  ...props.options
})

watch(() => props.modelValue, (value) => {
  setValue(value)
})
</script>
