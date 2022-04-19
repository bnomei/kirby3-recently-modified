import RecentlyModifiedField from "./components/fields/RecentlyModified.vue";
import RecentlyModifiedSection from "./components/sections/RecentlyModified.vue";

panel.plugin("bnomei/recently-modified", {
  fields: {
    recentlymodified: RecentlyModifiedField,
  },
  sections: {
    recentlymodified: RecentlyModifiedSection
  },
});
