<template>
  <section class="k-section k-links-section">
    <header class="k-section-header">
      <h2 class="k-label k-section-label">
        <span class="k-label-text">{{ this.labelOrHeadline }}</span>
      </h2>
    </header>
    <k-collection
        :items="links"
        :layout="layout"
    />
  </section>
</template>

<script>
export default {
  data() {
    return {
      label: undefined,
      headline: undefined,
      layout: "list",
      links: []
    }
  },
  created() {
    this.load().then(response => {
      this.label = response.label?.length > 0 ? response.label : undefined;
      this.headline = response.headline?.length > 0 ? response.headline : undefined;
      this.links = response.query; // resolved query = list
    });
  },
  computed: {
    labelOrHeadline () {
      return this.label ?? this.headline ?? "Recently Modified";
    }
  }
};
</script>

<style>
  .k-links-section .k-item-info {
    font-variant-numeric: tabular-nums;
  }
</style>
