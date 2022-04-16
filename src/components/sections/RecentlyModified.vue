<template>
  <section class="k-links-section">
    <header class="k-section-header">
      <h2 class="k-headline">{{ headline }}</h2>
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
      headline: "Recently Modified",
      layout: "list",
      links: []
    }
  },
  created() {
    this.load().then(response => {
      this.headline = response.headline;
      this.links = response.query; // resolved query = list
    });
  }
};
</script>
