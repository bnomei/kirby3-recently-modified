<template>
  <k-field :name="name" :type="type" :label="label" :help="help" :input="false">
    <k-collection :items="items" :empty="{ icon: 'page', text: $t('pages.empty') }" />
  </k-field>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    name: String,
    type: String,
    label: String,
    help: [String, Object],
    items: Array,
  },
  mounted() {
    this.$events.emit('field.loaded', this);
  },
};
</script>

<style>
.k-field-type-recentlymodifiedlist .k-item-info {
  font-variant-numeric: tabular-nums;
}
</style>
