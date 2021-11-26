panel.plugin("adspectus/zotero", {
  blocks: {
    zoterolist: {
      template: `
        <div @dblclick="open">
        <div>Zotero <span v-if="content.bibtype === 'all'">List of All Items</span><span v-if="content.bibtype === 'collection'">List of All Items in Collection {{ content.collectionkey }}</span><span v-if="content.bibtype === 'tags'">List of All Items for given Tag(s)</span><span v-if="content.bibtype === 'mypub'">List of All Items in My Publications</span></div>
        <div>Show <span v-if="content.showall">all</span><span v-else>{{ content.limit }}</span> item(s) sorted by {{ content.sortfield }} in {{ content.order }} order</div>
        </div>
      `
    },
    zoteroitem: {
      template: `
        <div @dblclick="open">
        <div>Zotero Single Item {{ content.itemkey }}</div>
        </div>
      `
    },
  },
});
