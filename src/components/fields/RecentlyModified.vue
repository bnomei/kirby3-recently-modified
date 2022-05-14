<template>
  <k-field class="k-recentlymodified-field"
           :help="help"
           :label="label"
           :when="when">
    <k-box :theme="dynTheme" name="textfield" :text="dynText" class="recentlymodified-text"/>
  </k-field>
</template>

<script>
export default {
  props: {
    help: String,
    label: String,
    when: String,
    theme: {type: String, default: 'info'},
    loading: {type: Boolean, default: false},
    interval: {type: Number, default: 0},
  },
  data() {
    return {
      auser: undefined,
      datetime: undefined,
      hasUserChange: false,
    }
  },
  created() {
    this.syncContent()
    if (this.interval > 0) {
      setInterval(this.syncContent, this.interval * 1000)
    }
  },
  watch: {
    hasChanges() {
      // post save or on revert
      if (!this.hasChanges) {
        this.syncContent()
      }
    }
  },
  computed: {
    hasChanges() {
      return this.$store.getters["content/hasChanges"]();
    },
    dynText() {
      return this.auser + ', ' + this.datetime;
    },
    dynTheme() {
      if (this.hasUserChange) return 'negative';
      return this.hasChanges || this.loading ? 'notice' : this.theme;
    },
  },
  methods: {
    syncContent() {
      let contentId = this.$store.getters["content/id"]();
      this.$api.get('recentlymodified/field', {
        id: contentId.split('?')[0],
      })
          .then(response => {
            if (this.auser !== undefined && this.auser !== response.auser) {
              this.hasUserChange = true
            }
            this.auser = response.auser
            this.datetime = response.datetime
            this.loading = false
          })
          .catch(error => {
            this.loading = false
          })
    }
  },
};
</script>

<style>

</style>
