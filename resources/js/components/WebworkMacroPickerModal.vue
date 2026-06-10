<template>
  <div>
    <!-- ── View Source modal for custom macros ────────────────────────── -->
    <b-modal
      id="modal-webwork-macro-source"
      :title="sourceModal.name"
      size="lg"
      scrollable
    >
      <div class="mb-3 text-muted small">
        <span v-if="sourceModal.ownerName">Created by {{ sourceModal.ownerName }}</span>
        <span v-if="sourceModal.ownerName && sourceModal.updatedAt"> &middot; </span>
        <span v-if="sourceModal.updatedAt">Last updated {{ sourceModal.updatedAt }}</span>
      </div>
      <pre style="background:transparent; font-size:13px;">
        <code class="language-perl" v-html="sourceModal.highlighted" />
      </pre>
      <template #modal-footer>
        <b-button size="sm" @click="$bvModal.hide('modal-webwork-macro-source')">
          Close
        </b-button>
        <b-button
          size="sm"
          :variant="alreadyAdded(sourceModal) ? 'success' : 'primary'"
          :disabled="alreadyAdded(sourceModal)"
          @click="addMacroFromModal"
        >
          {{ alreadyAdded(sourceModal) ? 'Already Added' : `Add ${sourceModal.name}` }}
        </b-button>
      </template>
    </b-modal>

    <!-- ── Main picker modal ──────────────────────────────────────────── -->
    <b-modal
      id="modal-webwork-macro-picker"
      title="Add Macro"
      size="xl"
      hide-footer
      scrollable
      no-close-on-backdrop
    >
      <!-- Filters -->
      <b-form-row class="mb-3 align-items-center">
        <!-- Search -->
        <b-col cols="12" md="5" class="mb-2 mb-md-0">
          <b-form-input
            v-model="search"
            size="sm"
            placeholder="Search by name or description..."
          />
        </b-col>

        <!-- Source filter -->
        <b-col cols="auto" class="mb-2 mb-md-0">
          <b-form-radio-group
            v-model="filterSource"
            :options="sourceFilterOptions"
            buttons
            button-variant="outline-secondary"
            size="sm"
          />
        </b-col>
      </b-form-row>
      <p class="text-muted small mb-3">
        To create or edit custom macros,
        <a href="https://support.libretexts.org" target="_blank">contact us</a> to request access.
      </p>
      <div v-if="filteredMacros.length">
        <table class="table table-striped table-sm">
          <thead>
          <tr>
            <th />
            <th>Name</th>
            <th>Description</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="macro in filteredMacros" :key="macro.id">
            <!-- Add icon -->
            <td style="width:40px; text-align:center">
                <span :id="`macro-add-tooltip-${macro.id}`">
                  <b-icon
                    :icon="alreadyAdded(macro) ? 'check-circle-fill' : 'plus-circle'"
                    :variant="alreadyAdded(macro) ? 'success' : 'primary'"
                    style="cursor:pointer; font-size:1.2rem"
                    @click="addMacro(macro)"
                  />
                </span>
              <b-tooltip
                :target="`macro-add-tooltip-${macro.id}`"
                triggers="hover"
                :delay="200"
              >
                {{ alreadyAdded(macro) ? 'Already added' : `Add ${macro.name}` }}
              </b-tooltip>
            </td>

            <!-- Icon + Name inline, icon to the left -->
            <td style="width:240px; font-family:monospace; font-size:13px">
              <div class="d-flex align-items-center">
                <!-- Source icon to the LEFT of name -->
                <span
                  :id="`macro-source-tooltip-${macro.id}`"
                  class="mr-2 flex-shrink-0"
                  style="line-height:1"
                >
                    <b-icon
                      v-if="macro.source === 'standard'"
                      icon="book-fill"
                      style="font-size:0.85rem; color:#0d9488;"
                    />
                    <b-icon
                      v-else
                      icon="tools"
                      variant="secondary"
                      style="font-size:0.85rem"
                    />
                  </span>

                <b-tooltip
                  :target="`macro-source-tooltip-${macro.id}`"
                  triggers="hover"
                  :delay="500"
                >
                  <span v-if="macro.source === 'standard'">Core PG macro</span>
                  <span v-else>
                      Custom macro
                      <span v-if="macro.owner_name"><br>Created by {{ macro.owner_name }}</span>
                      <span v-if="macro.updated_at"><br>Last updated {{ formatDate(macro.updated_at) }}</span>
                    </span>
                </b-tooltip>

                <!-- Name link with tooltip -->
                <span :id="`macro-name-tooltip-${macro.id}`">
                    <a
                      v-if="macro.source === 'standard'"
                      :href="macro.macro"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      {{ macro.name }}
                    </a>
                    <a
                      v-else
                      href=""
                      @click.prevent="openSourceModal(macro)"
                    >
                      {{ macro.name }}
                    </a>
                  </span>

                <b-tooltip
                  :target="`macro-name-tooltip-${macro.id}`"
                  triggers="hover"
                  :delay="500"
                >
                  {{ macro.source === 'standard' ? 'View source on GitHub' : 'View macro source' }}
                </b-tooltip>
              </div>
            </td>

            <!-- Description -->
            <td>    <span v-html="macro.description || 'None available'"/></td>
          </tr>
          </tbody>
        </table>
      </div>

      <b-alert v-else show variant="info">
        No macros match your search.
      </b-alert>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import Prism from 'prismjs'
import 'prismjs/components/prism-perl'
import 'prismjs/themes/prism.css'

export default {
  name: 'WebworkMacroPickerModal',

  props: {
    currentCode: {
      type: String,
      default: ''
    }
  },

  data: () => ({
    macros: [],
    search: '',
    recentlyAdded: [],
    filterSource: 'all',
    filterDeprecated: 'current',
    sourceModal: {
      id: null,
      name: '',
      highlighted: '',
      ownerName: '',
      updatedAt: ''
    }
  }),

  computed: {
    sourceFilterOptions () {
      return [
        { text: 'All', value: 'all' },
        { text: 'Core', value: 'standard' },
        { text: 'Custom', value: 'custom' }
      ]
    },

    filteredMacros () {
      const q = this.search.toLowerCase()

      return this.macros.filter(m => {
        // Source filter
        if (this.filterSource !== 'all' && m.source !== this.filterSource) return false

        // Deprecated filter — checks if description contains 'deprecated' (case-insensitive)
        const isDeprecated = m.description && m.description.toLowerCase().includes('deprecated')
        if (this.filterDeprecated === 'current' && isDeprecated) return false
        if (this.filterDeprecated === 'deprecated' && !isDeprecated) return false

        // Text search
        if (!q) return true
        return (
          m.name.toLowerCase().includes(q) ||
          (m.description && m.description.toLowerCase().includes(q))
        )
      })
    }
  },

  async mounted () {
    await this.getMacros()
  },

  methods: {
    highlight (code) {
      return Prism.highlight(code, Prism.languages.perl, 'perl')
    },

    formatDate (dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr)
      return `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`
    },

    async getMacros () {
      try {
        const { data } = await axios.get('/api/webwork-macros')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.macros = data.webwork_macros
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    alreadyAdded (macro) {
      if (!macro || !macro.name) return false
      if (this.recentlyAdded.includes(macro.id)) return false
      return this.currentCode.includes(`"${macro.name}"`)
    },

    addMacro (macro) {
      if (this.alreadyAdded(macro)) {
        this.$noty.info(`${macro.name} is already in your code.`)
        return
      }
      this.$emit('insert', `loadMacros("${macro.name}");\n`)
      this.$noty.success(`loadMacros("${macro.name}") added to your code.`)
      this.recentlyAdded.push(macro.id)
      setTimeout(() => {
        this.recentlyAdded = this.recentlyAdded.filter(id => id !== macro.id)
      }, 1500)
    },

    addMacroFromModal () {
      this.addMacro(this.sourceModal)
      this.$bvModal.hide('modal-webwork-macro-source')
    },

    openSourceModal (macro) {
      this.sourceModal = {
        id: macro.id,
        name: macro.name,
        highlighted: this.highlight(macro.macro),
        ownerName: macro.owner_name || '',
        updatedAt: macro.updated_at ? this.formatDate(macro.updated_at) : ''
      }
      this.$bvModal.show('modal-webwork-macro-source')
    }
  }
}
</script>

<style scoped>
:deep(pre[class*="language-"]),
:deep(code[class*="language-"]) {
  background: transparent;
}
</style>
