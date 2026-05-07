<template>
  <div>
    <ckeditor
      id="qtiNumericalPrompt"
      v-model="qtiJson.prompt"
      tabindex="0"
      required
      :config="richEditorConfig"
      :class="{ 'is-invalid': questionForm.errors.has('qti_prompt') }"
      class="pb-3"
      @namespaceloaded="onCKEditorNamespaceLoaded"
      @ready="handleFixCKEditor()"
      @focus="setCKEditorKeydownAsTrue(); questionForm.errors.clear('qti_prompt')"
      @keydown="questionForm.errors.clear('qti_prompt')"
    />
    <has-error :form="questionForm" field="qti_prompt" />

    <div v-if="uTags && uTags.length" class="mt-2">
      <b-alert
        v-if="placeholderMismatch"
        show
        variant="warning"
        class="py-2"
      >
        The number of underlined blanks in the prompt ({{ uTags.length }}) does not match
        the number of saved answers ({{ qtiJson.placeholders ? qtiJson.placeholders.length : 0 }}).
        Please review the table below.
      </b-alert>

      <table class="table table-striped">
        <thead>
          <tr>
            <th>Blank</th>
            <th>Tolerance Type</th>
            <th>Tolerance</th>
            <th>Accepted Range</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(tag, index) in uTags" :key="`numerical-blank-${index}`">
            <td class="align-middle">
              <strong>{{ index + 1 }}</strong>
              <span class="text-muted ml-1">({{ tag }})</span>
              <div v-if="placeholderErrors[index] && placeholderErrors[index].value" class="invalid-feedback d-block">{{ placeholderErrors[index].value }}</div>
            </td>
            <td>
              <b-form-radio-group
                v-if="placeholders[index]"
                v-model="placeholders[index].toleranceType"
                :name="`tolerance-type-${index}`"
                :options="toleranceTypeOptions"
                size="sm"
                @change="onToleranceTypeChange(index)"
              />
            </td>
            <td>
              <div v-if="placeholders[index]">
                <b-input-group
                  v-if="placeholders[index].toleranceType === 'absolute'"
                  append="±"
                  size="sm"
                  style="width:110px"
                >
                  <b-form-input
                    v-model="placeholders[index].absoluteTolerance"
                    type="text"
                    :class="{ 'is-invalid': questionForm.errors.has(`placeholders.${index}.absoluteTolerance`) }"
                    @keydown="clearPlaceholderError(index, 'absoluteTolerance')"
                    @input="syncToQtiJson"
                  />
                </b-input-group>
                <b-input-group
                  v-if="placeholders[index].toleranceType === 'relative'"
                  append="%"
                  size="sm"
                  style="width:110px"
                >
                  <b-form-input
                    v-model="placeholders[index].relativeTolerance"
                    type="text"
                    :class="{ 'is-invalid': questionForm.errors.has(`placeholders.${index}.relativeTolerance`) }"
                    @keydown="clearPlaceholderError(index, 'relativeTolerance')"
                    @input="syncToQtiJson"
                  />
                </b-input-group>
                <div v-if="placeholderErrors[index] && placeholderErrors[index].absoluteTolerance" class="invalid-feedback d-block">{{ placeholderErrors[index].absoluteTolerance }}</div>
                <div v-if="placeholderErrors[index] && placeholderErrors[index].relativeTolerance" class="invalid-feedback d-block">{{ placeholderErrors[index].relativeTolerance }}</div>
              </div>
            </td>
            <td class="align-middle text-muted small">
              <span v-if="placeholders[index]">{{ rangePreview(placeholders[index]) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<script>
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'

export default {
  name: 'MultiNumerical',
  components: {
    ckeditor: CKEditor.component
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {}
    },
    richEditorConfig: {
      type: Object,
      default: () => {}
    },
    questionForm: {
      type: Object,
      default: () => {}
    }
  },
  data () {
    return {
      placeholders: [],
      placeholderErrors: {},
      toleranceTypeOptions: [
        { text: 'Absolute (±)', value: 'absolute' },
        { text: 'Relative (%)', value: 'relative' }
      ]
    }
  },
  computed: {
    uTags () {
      if (!this.qtiJson.prompt) return []
      const regex = /(<u>.*?<\/u>)/
      const matches = String(this.qtiJson.prompt).split(regex).filter(Boolean)
      const tags = []
      for (const match of matches) {
        if (match.includes('<u>') && match.includes('</u>')) {
          tags.push(this.htmlDecode(match.replace(/<\/?u>/g, '').trim()))
        }
      }
      return tags
    },
    placeholderMismatch () {
      return this.qtiJson.placeholders &&
        this.qtiJson.placeholders.length &&
        this.qtiJson.placeholders.length !== this.uTags.length
    }
  },
  watch: {
    uTags (newTags) {
      this.syncPlaceholdersToTags(newTags)
    }
  },
  mounted () {
    if (this.qtiJson.placeholders && this.qtiJson.placeholders.length) {
      // New format — hydrate directly
      this.placeholders = this.qtiJson.placeholders.map(p => ({ ...p }))
    } else if (this.qtiJson.correctResponse) {
      // Legacy single-answer — migrate into placeholders shape
      this.placeholders = [{
        value: this.qtiJson.correctResponse.value ?? '',
        toleranceType: 'absolute',
        absoluteTolerance: this.qtiJson.correctResponse.marginOfError ?? '0',
        relativeTolerance: '0'
      }]
      this.syncToQtiJson()
    }
  },
  methods: {
    syncPlaceholdersToTags (tags) {
      // Grow or shrink the placeholders array to match the number of <u> tags.
      // The underlined text always becomes the correct value (auto-updated).
      // Tolerance settings are preserved when a placeholder already exists.
      this.placeholders = tags.map((tag, i) => {
        const existing = this.placeholders[i] || this.emptyPlaceholder()
        return { ...existing, value: tag }
      })
      this.syncToQtiJson()
    },
    emptyPlaceholder () {
      return {
        value: '',
        toleranceType: 'absolute',
        absoluteTolerance: '0',
        relativeTolerance: '0'
      }
    },
    onToleranceTypeChange (index) {
      // Reset the unused field so stale values don't get saved
      if (this.placeholders[index].toleranceType === 'absolute') {
        this.placeholders[index].relativeTolerance = '0'
      } else {
        this.placeholders[index].absoluteTolerance = '0'
      }
      this.syncToQtiJson()
    },
    setErrors (errors) {
      // Called by CreateQuestion.vue after a failed save.
      // errors is the parsed JSON object from ValidNumericalPlaceholders.
      this.placeholderErrors = errors
    },
    clearPlaceholderError (index, field) {
      if (this.placeholderErrors[index]) {
        this.$delete(this.placeholderErrors[index], field)
      }
    },
    syncToQtiJson () {
      this.$set(this.qtiJson, 'placeholders', this.placeholders.map(p => ({ ...p })))
      // Keep legacy correctResponse mirroring placeholder[0] for backwards compat
      if (this.placeholders.length) {
        const first = this.placeholders[0]
        this.$set(this.qtiJson, 'correctResponse', {
          value: first.value,
          marginOfError: first.toleranceType === 'absolute' ? first.absoluteTolerance : '0'
        })
      }
    },
    rangePreview (placeholder) {
      const val = parseFloat(placeholder.value)
      if (isNaN(val)) return '—'
      if (placeholder.toleranceType === 'absolute') {
        const tol = parseFloat(placeholder.absoluteTolerance)
        if (isNaN(tol) || tol < 0) return '—'
        if (tol === 0) return `Exact: ${val}`
        return `${+(val - tol).toFixed(6)} to ${+(val + tol).toFixed(6)}`
      } else {
        const pct = parseFloat(placeholder.relativeTolerance)
        if (isNaN(pct) || pct < 0) return '—'
        if (pct === 0) return `Exact: ${val}`
        const tol = Math.abs(val) * pct / 100
        return `${+(val - tol).toFixed(6)} to ${+(val + tol).toFixed(6)} (±${pct}%)`
      }
    },
    htmlDecode (input) {
      const doc = new DOMParser().parseFromString(input, 'text/html')
      return doc.documentElement.textContent
    },
    setCKEditorKeydownAsTrue () {
      this.$emit('setCKEditorKeydownAsTrue')
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    }
  }
}
</script>
