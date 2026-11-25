import RecentlyModifiedField from "./components/fields/RecentlyModified.vue";
import RecentlyModifiedList from "./components/fields/RecentlyModifiedList.vue";

panel.plugin("bnomei/recently-modified", {
  fields: {
    recentlymodified: RecentlyModifiedField,
    recentlymodifiedlist: RecentlyModifiedList,
  },
});
