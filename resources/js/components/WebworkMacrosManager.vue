<template>
  <div>
    <!-- ── Compare revisions modal (admin only) ─────────────────────── -->
    <b-modal
      id="modal-compare-revisions"
      :title="`Compare Revisions for ${comparingMacroName}`"
      size="xl"
      scrollable
      hide-footer
      no-close-on-backdrop
    >
      <b-form-group>
        <b-form-row>
          <b-form-select
            v-model="revision1Id"
            style="width:500px"
            size="sm"
            :options="revisionOptions"
            class="mt-2 mr-2"
            @change="compareRevisions"
          />
          <b-form-select
            v-model="revision2Id"
            style="width:500px"
            size="sm"
            :options="revisionOptions"
            class="mt-2 mr-2"
            @change="compareRevisions"
          />
        </b-form-row>
      </b-form-group>
      <MacroRevisionDifferences
        v-if="revision1 && revision2"
        :key="`macro-revision-differences-${macroRevisionDifferencesKey}`"
        :revision1="revision1"
        :revision2="revision2"
        :diffs-shown="diffsShown"
        :max-revision-number="maxRevisionNumber"
        :differences="differences"
        @reloadMacroRevisionDifferences="reloadMacroRevisionDifferences"
      />
    </b-modal>

    <!-- ── Delete confirmation modal ──────────────────────────────────── -->
    <b-modal id="modal-confirm-delete-webwork-macro" :title="`Retire ${macroToDelete.name}`">
      <p>You are about to retire the macro:</p>
      <p class="text-center"><strong>{{ macroToDelete.name }}</strong></p>
      <b-alert show variant="info">
        Retired macros will no longer appear in the list. The name cannot be reused.
      </b-alert>
      <template #modal-footer>
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-delete-webwork-macro')">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" @click="destroyMacro">Retire</b-button>
      </template>
    </b-modal>

    <!-- ── Add / Edit / Clone modal ───────────────────────────────────── -->
    <b-modal
      id="modal-webwork-macro-form"
      :title="isEdit ? `Edit ${macroForm.name}` : (isClone ? `Clone ${macroForm.name}` : 'Add Macro')"
      size="xl"
      dialog-class="modal-90"
      no-close-on-backdrop
      @shown="macroFormModalVisible = true; refreshCodeMirror()"
      @hidden="macroFormModalVisible = false"
    >
      <b-alert v-if="isClone" show variant="info">
        You are cloning <strong>{{ cloningFromName }}</strong>. Please give this macro a new name before saving.
      </b-alert>

      <b-form-group label-cols-sm="2" label-cols-lg="1" label-for="macro-name" label="Name*">
        <b-form-input
          id="macro-name"
          v-model="macroForm.name"
          size="sm"
          type="text"
          :disabled="isEdit"
          :class="{ 'is-invalid': macroForm.errors.has('name') }"
          @keydown="macroForm.errors.clear('name')"
        />
        <has-error :form="macroForm" field="name"/>
      </b-form-group>

      <!-- Revision select — only shown when editing a macro that has revisions -->
      <b-form-group
        v-if="isEdit && revisions.length"
        label-cols-sm="2"
        label-cols-lg="1"
        label="Revision"
      >
        <b-form-row class="align-items-center">
          <b-form-select
            v-model="editRevisionId"
            style="width:500px"
            size="sm"
            :options="revisionOptions"
            class="mr-2"
            @change="loadRevisionIntoForm"
          />
          <b-button
            size="sm"
            variant="outline-primary"
            @click="openCompareFromEdit"
          >
            Compare Revisions
          </b-button>
        </b-form-row>
      </b-form-group>

      <b-form-group
        label-cols-sm="2"
        label-cols-lg="1"
        label-for="macro-description"
        label="Description*"
      >
        <ckeditor
          v-if="macroFormModalVisible"
          id="macro-description"
          v-model="macroForm.description"
          :config="richEditorConfig"
          tabindex="0"
          :class="{ 'is-invalid': macroForm.errors.has('description') }"
          @keydown="macroForm.errors.clear('description')"
        />
        <has-error :form="macroForm" field="description"/>
      </b-form-group>

      <b-row class="justify-content-end mb-2 pr-3">
        <b-button
          variant="outline-primary"
          size="sm"
          :disabled="!macroForm.macro || !macroForm.macro.trim()"
          @click="exportMacroCode"
        >
          Export code
        </b-button>
      </b-row>
      <b-form-group label-cols-sm="2" label-cols-lg="1" label-for="macro-body" label="Macro*">
        <CodeMirrorEditor
          ref="codeMirrorEditor"
          :key="`codemirror-${codeMirrorKey}`"
          v-model="macroForm.macro"
          :is-invalid="macroForm.errors.has('macro')"
          @input="macroForm.errors.clear('macro')"
        />
        <has-error :form="macroForm" field="macro"/>
      </b-form-group>

      <!-- Reason for edit only shown when editing (not cloning) -->
      <b-form-group
        v-if="isEdit"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="reason-for-edit"
        label="Reason for Edit*"
      >
        <b-form-input
          id="reason-for-edit"
          v-model="macroForm.reason_for_edit"
          size="sm"
          type="text"
          :class="{ 'is-invalid': macroForm.errors.has('reason_for_edit') }"
          @input="macroForm.errors.clear('reason_for_edit')"
        />
        <has-error :form="macroForm" field="reason_for_edit"/>
      </b-form-group>

      <template #modal-footer>
        <b-button size="sm" @click="$bvModal.hide('modal-webwork-macro-form')">Cancel</b-button>
        <b-button size="sm" variant="primary" @click="saveMacro">Submit</b-button>
      </template>
    </b-modal>

    <!-- ── Main content ───────────────────────────────────────────────── -->
    <div class="vld-parent">
      <loading
        :active.sync="isLoading"
        :can-cancel="true"
        :is-full-page="true"
        :width="128"
        :height="128"
        color="#007BFF"
        background="#FFFFFF"
      />

      <div v-if="!isLoading">
        <!-- Toolbar -->
        <b-row align-h="between" class="mb-3 align-items-end">
          <b-col cols="auto">
            <b-form-row v-if="isAdmin" class="align-items-end">
              <b-col cols="auto">
                <label class="mb-1 small font-weight-bold">Filter by Creator</label>
                <b-form-select
                  v-model="filterUserId"
                  size="sm"
                  style="width:240px"
                  :options="creatorOptions"
                  @change="getMacros"
                />
              </b-col>
            </b-form-row>
          </b-col>
          <b-col cols="auto">
            <b-button
              v-if="canCreate"
              variant="primary"
              size="sm"
              @click="initAddMacro"
            >
              New Macro
            </b-button>
          </b-col>
        </b-row>

        <div v-if="webworkMacros.length">
          <table class="table table-striped" aria-label="WeBWork Macros List">
            <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Description</th>
              <th v-if="isAdmin" scope="col">Created By</th>
              <th scope="col">Last Updated</th>
              <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <tr
              v-for="macro in webworkMacros"
              :key="`webwork-macro-${macro.name}`"
              :class="{ 'table-info': macro.in_question }"
            >
              <th scope="row" style="width:200px">
                {{ macro.name }}
                <b-badge v-if="macro.in_question" variant="info" class="ml-1">
                  In Question
                </b-badge>
              </th>
              <td style="width:220px">
                <span v-html="macro.description || 'None available'"/>
              </td>
              <td v-if="isAdmin" style="width:160px">{{ macro.owner_name }}</td>
              <td style="width:90px; white-space:nowrap">
                {{ formatDate(macro.updated_at) }}
              </td>
              <td style="width:120px">
                <span :id="`edit-macro-${macro.id}`">
                  <b-icon
                    v-if="macro.can_edit"
                    class="text-muted mr-1"
                    icon="pencil"
                    :aria-label="`Edit ${macro.name}`"
                    style="cursor:pointer"
                    @click="initEditMacro(macro)"
                  />
                </span>
                <b-tooltip
                  v-if="macro.can_edit"
                  :target="`edit-macro-${macro.id}`"
                  triggers="hover"
                  :delay="{ show: 500, hide: 0 }"
                >
                  Edit {{ macro.name }}
                </b-tooltip>

                <span :id="`delete-macro-${macro.id}`">
                  <b-icon
                    v-if="macro.can_edit"
                    class="text-muted mr-1"
                    icon="trash"
                    :aria-label="`Retire ${macro.id}`"
                    style="cursor:pointer"
                    @click="initDeleteMacro(macro)"
                  />
                </span>
                <b-tooltip
                  v-if="macro.can_edit"
                  :target="`delete-macro-${macro.id}`"
                  triggers="hover"
                  :delay="{ show: 500, hide: 0 }"
                >
                  Retire {{ macro.name }}.
                </b-tooltip>

                <span :id="`clone-macro-${macro.id}`" class="mr-2">
                  <font-awesome-icon
                    v-if="canCreate"
                    :icon="copyIcon"
                    class="text-muted"
                    style="cursor:pointer"
                    @click="initCloneMacro(macro)"
                  />
                </span>
                <b-tooltip
                  v-if="canCreate"
                  :target="`clone-macro-${macro.id}`"
                  triggers="hover"
                  :delay="{ show: 500, hide: 0 }"
                >
                  Clone {{ macro.name }}
                </b-tooltip>
              </td>
            </tr>
            </tbody>
          </table>
        </div>

        <div v-else>
          <b-alert show variant="info">No WeBWork macros found.</b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import Form from 'vform/src'
import axios from 'axios'
import CKEditor from 'ckeditor4-vue'
import MacroRevisionDifferences from '~/components/MacroRevisionDifferences'
import CodeMirrorEditor from '~/components/CodeMirrorEditor'

const defaultMacroForm = {
  name: '',
  description: '',
  macro: '',
  reason_for_edit: ''
}

export default {
  name: 'WebworkMacrosManager',

  components: {
    FontAwesomeIcon,
    Loading,
    ckeditor: CKEditor.component,
    MacroRevisionDifferences,
    CodeMirrorEditor
  },

  props: {},

  data: () => ({
    macroToView: {},
    differences: [],
    copyIcon: faCopy,
    isLoading: true,
    webworkMacros: [],
    macroToDelete: {},
    isEdit: false,
    isClone: false,
    cloningFromName: '',
    editingMacroId: null,
    macroForm: new Form({ ...defaultMacroForm }),
    filterUserId: '',
    creators: [],
    canCreate: false,
    isAdmin: false,
    revisions: [],
    revision1Id: null,
    revision2Id: null,
    revision1: null,
    revision2: null,
    diffsShown: true,
    macroRevisionDifferencesKey: 0,
    comparingMacroName: '',
    codeMirrorKey: 0,
    macroFormModalVisible: false,
    editRevisionId: null,

    richEditorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', '-', 'Undo', 'Redo'] },
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat'] },
        { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
        { name: 'links', items: ['Link', 'Unlink'] }
      ],
      resize_enabled: false,
      height: 120,
      toolbarLocation: 'top',
      floatSpaceDockedOffsetY: 0,
      baseFloatZIndex: 10000,
      removePlugins: 'floatingspace,resize'
    }
  }),

  computed: {
    creatorOptions () {
      const options = [{ value: '', text: 'All Creators' }]
      this.creators.forEach(c => {
        options.push({
          value: c.id,
          text: `${c.first_name} ${c.last_name}`
        })
      })
      return options
    },

    revisionOptions () {
      const placeholder = [{ value: null, text: 'Select a revision…' }]
      return placeholder.concat(
        this.revisions.map(r => {
          let label
          if (r.revision_number === 0) {
            label = 'Original'
          } else if (r.revision_number === this.maxRevisionNumber) {
            label = 'Current'
          } else {
            label = `Rev ${r.revision_number}`
          }
          return {
            value: r.id,
            text: `${label} — ${r.editor_name} — ${this.formatDateTime(r.created_at)}${r.reason_for_edit ? ' — ' + r.reason_for_edit : ''}`
          }
        })
      )
    },

    maxRevisionNumber () {
      if (!this.revisions.length) return null
      return Math.max(...this.revisions.map(r => r.revision_number))
    }
  },

  async mounted () {
    await this.getMacros()
    this.isLoading = false
  },

  methods: {
    exportMacroCode () {
      const code = this.macroForm.macro || ''
      const filename = this.macroForm.name
        ? this.macroForm.name.endsWith('.pl') ? this.macroForm.name : this.macroForm.name + '.pl'
        : 'macro.pl'
      const blob = new Blob([code], { type: 'text/plain' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    },
    refreshCodeMirror () {
      this.$nextTick(() => {
        const cm = this.$refs.codeMirrorEditor
        if (cm && cm.editor) {
          cm.editor.refresh()
        }
      })
    },

    formatDate (dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr)
      return `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)}`
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
    },

    async getMacros () {
      try {
        const params = {}
        const { data } = await axios.get('/api/webwork-macros', { params })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.webworkMacros = data.webwork_macros
          .filter(m => m.source === 'custom')
          .filter(m => !this.filterUserId || (m.owner_id && m.owner_id === this.filterUserId))
          .sort((a, b) => a.name.localeCompare(b.name))
        this.canCreate = data.can_create
        this.isAdmin = data.is_admin
        if (data.is_admin && data.creators) {
          this.creators = data.creators
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    initAddMacro () {
      this.isEdit = false
      this.isClone = false
      this.cloningFromName = ''
      this.editingMacroId = null
      this.revisions = []
      this.editRevisionId = null
      this.macroForm = new Form({ ...defaultMacroForm })
      this.codeMirrorKey++
      this.$bvModal.show('modal-webwork-macro-form')
    },

    async initEditMacro (macro) {
      try {
        const { data } = await axios.get(`/api/webwork-macros/source/${macro.name}`)
        if (data.type === 'success') {
          this.isEdit = true
          this.isClone = false
          this.cloningFromName = ''
          this.editingMacroId = macro.id
          this.macroForm = new Form({
            name: macro.name,
            description: macro.description,
            macro: data.macro ? data.macro.trim() : '',
            reason_for_edit: ''
          })
          this.codeMirrorKey++

          // fetch revisions
          this.revisions = []
          this.editRevisionId = null
          try {
            const revData = await axios.get(`/api/webwork-macros/${macro.id}/revisions`)
            if (revData.data.type !== 'error') {
              this.revisions = revData.data.revisions
              // default select to current revision
              const current = this.revisions.find(r => r.revision_number === this.maxRevisionNumber)
              if (current) {
                this.editRevisionId = current.id
              }
            }
          } catch (revError) {
            // revisions are optional — silently ignore if unavailable
          }

          this.$bvModal.show('modal-webwork-macro-form')
        } else {
          this.$noty.error(data.type)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    initCloneMacro (macro) {
      this.isEdit = false
      this.isClone = true
      this.cloningFromName = macro.name
      this.editingMacroId = null
      this.revisions = []
      this.editRevisionId = null
      this.macroForm = new Form({
        name: '',
        description: macro.description,
        macro: macro.macro ? macro.macro.trim() : '',
        reason_for_edit: ''
      })
      this.codeMirrorKey++
      this.$bvModal.show('modal-webwork-macro-form')
    },

    initDeleteMacro (macro) {
      this.macroToDelete = macro
      this.$bvModal.show('modal-confirm-delete-webwork-macro')
    },

    loadRevisionIntoForm () {
      const revision = this.revisions.find(r => r.id === this.editRevisionId)
      if (!revision) return
      this.macroForm.macro = revision.macro ? revision.macro.trim() : ''
      const revLabel = revision.revision_number === 0
        ? 'original'
        : `revision ${revision.revision_number}`
      this.macroForm.reason_for_edit = `Restored to ${revLabel} (${revision.editor_name}, ${this.formatDateTime(revision.created_at)})`
      this.codeMirrorKey++
    },

    openCompareFromEdit () {
      this.macroToView = { id: this.editingMacroId, name: this.macroForm.name }
      this.comparingMacroName = this.macroForm.name
      this.revision1Id = null
      this.revision2Id = null
      this.revision1 = null
      this.revision2 = null
      this.differences = []
      this.diffsShown = true
      this.$bvModal.show('modal-compare-revisions')
    },

    async saveMacro () {
      try {
        if (this.macroForm.name && !this.macroForm.name.endsWith('.pl')) {
          this.macroForm.name = this.macroForm.name + '.pl'
        }
        const { data } = this.isEdit
          ? await this.macroForm.patch(`/api/webwork-macros/${this.editingMacroId}`)
          : await this.macroForm.post('/api/webwork-macros')
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-webwork-macro-form')
          await this.getMacros()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },

    async destroyMacro () {
      try {
        const { data } = await axios.delete(`/api/webwork-macros/${this.macroToDelete.id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.webworkMacros = this.webworkMacros.filter(m => m.id !== this.macroToDelete.id)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-confirm-delete-webwork-macro')
    },

    async compareRevisions () {
      if (!this.revision1Id || !this.revision2Id) return
      this.revision1 = null
      this.revision2 = null
      this.differences = []

      const { data } = await axios.get(
        `/api/webwork-macros/${this.macroToView.id}/diff/${this.revision1Id}/${this.revision2Id}`
      )
      if (data.type === 'error') {
        this.$noty.error(data.message)
        return
      }
      this.differences = data.differences
      this.revision1 = data.revision1
      this.revision2 = data.revision2
      this.macroRevisionDifferencesKey++
    },

    reloadMacroRevisionDifferences (diffsShown) {
      this.diffsShown = diffsShown
      this.macroRevisionDifferencesKey++
    },

  }
}
</script>

<style scoped>
.macro-preview {
  background: transparent;
  border: none;
  border-radius: 4px;
  padding: 8px 12px;
  margin: 0;
  max-height: 150px;
  overflow-y: auto;
  font-size: 12px;
}
</style>
