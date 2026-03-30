<template>
  <div>
    <div v-if="!differences.length">
      <b-alert show variant="info">
        These two revisions are identical — no differences found.
      </b-alert>
    </div>
    <div v-if="differences.length" class="mb-2">
      <b-button
        v-show="!diffsShown"
        size="sm"
        variant="info"
        @click="$emit('reloadMacroRevisionDifferences', true)"
      >
        Show Diffs
      </b-button>
      <b-button
        v-show="diffsShown"
        size="sm"
        @click="$emit('reloadMacroRevisionDifferences', false)"
      >
        Hide Diffs
      </b-button>
    </div>
    <table v-if="differences.length" class="table table-striped">
      <thead>
      <tr>
        <th>Property</th>
        <th>
          <span v-if="revision1.revision_number === 0">Original</span>
          <span v-else-if="isCurrentRevision(revision1)">Current</span>
          <span v-else>Revision {{ revision1.revision_number }}</span>
          <div class="small font-weight-normal text-muted">
            {{ formatDateTime(revision1.created_at) }} — {{ revision1.editor_name }}
          </div>
        </th>
        <th>
          <span v-if="revision2.revision_number === 0">Original</span>
          <span v-else-if="isCurrentRevision(revision2)">Current</span>
          <span v-else>Revision {{ revision2.revision_number }}</span>
          <div class="small font-weight-normal text-muted">
            {{ formatDateTime(revision2.created_at) }} — {{ revision2.editor_name }}
          </div>
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(difference, i) in differences" :key="`macro-diff-${i}`">
        <td>{{ difference.property }}</td>
        <td><div v-html="difference.revision1" /></td>
        <td v-show="diffsShown"><div v-html="difference.revision2" /></td>
        <td v-show="!diffsShown"><div v-html="difference.revision2NoDiffs" /></td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'MacroRevisionDifferences',

  props: {
    differences:       { type: Array,   default: () => [] },
    revision1:         { type: Object,  default: () => ({}) },
    revision2:         { type: Object,  default: () => ({}) },
    diffsShown:        { type: Boolean, default: true },
    maxRevisionNumber: { type: Number,  default: null }
  },

  methods: {
    isCurrentRevision (revision) {
      return this.maxRevisionNumber !== null &&
        revision.revision_number === this.maxRevisionNumber
    },

    formatDateTime (dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr)
      const date = `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`
      const hours = d.getHours()
      const minutes = String(d.getMinutes()).padStart(2, '0')
      const ampm = hours >= 12 ? 'PM' : 'AM'
      const h = hours % 12 || 12
      return `${date} ${h}:${minutes} ${ampm}`
    }
  }
}
</script>
