<template>
  <k-field :name="name" :type="type" :label="label" :help="help" :input="false">
    <k-box :theme="boxTheme" :text="boxText" />
  </k-field>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    modifier: Object,
    endpoints: Object,
    help: [String, Object],
    interval: Number,
    label: String,
    name: String,
    theme: String,
    type: String,
  },
  data() {
    return { edit: null, changedEditor: false, error: null, request: 0, timer: null };
  },
  computed: {
    hasChanges() {
      return this.$panel.content.hasDiff();
    },
    boxTheme() {
      if (this.error || this.changedEditor) return 'negative';
      return this.hasChanges ? 'notice' : this.theme;
    },
    boxText() {
      if (this.error) return this.error;
      return [this.edit.auser, this.edit.datetime].filter(Boolean).join(', ');
    },
  },
  watch: {
    'endpoints.field': {
      immediate: true,
      handler() {
        this.request++;
        clearTimeout(this.timer);
        this.edit = this.modifier;
        this.changedEditor = false;
        this.error = null;
        this.poll();
      },
    },
    hasChanges(changed) {
      if (!changed) this.refresh();
    },
  },
  beforeUnmount() {
    this.request++;
    clearTimeout(this.timer);
  },
  methods: {
    poll() {
      if (this.interval > 0) this.timer = setTimeout(this.refresh, this.interval * 1000);
    },
    async refresh() {
      const request = ++this.request;
      clearTimeout(this.timer);
      try {
        const edit = await this.$api.get(this.endpoints.field);
        if (request !== this.request) return;
        if (this.edit.auser !== edit.auser) {
          this.changedEditor = edit.auser !== this.$panel.user.username;
        }
        this.edit = edit;
        this.error = null;
      } catch (error) {
        if (request === this.request) this.error = error.message;
      } finally {
        if (request === this.request) this.poll();
      }
    },
  },
};
</script>
