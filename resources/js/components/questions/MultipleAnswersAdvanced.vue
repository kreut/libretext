<template>
  <div>
    <b-modal id="modal-single-marker-sketcher-viewer"
             title="View Mark"
             no-close-on-backdrop
             size="lg"
    >
      <iframe
        id="single-marker-sketcher-viewer"
        v-resize="{ log: false }"
        width="100%"
        src="/api/sketcher/readonly"
        frameborder="0"
        @load="loadStructure"
      />
    </b-modal>
    <b-table
      aria-label="'Atoms and Bonds"
      striped
      hover
      :no-border-collapse="true"
      :fields="atomsAndBondsFields"
      :items="atomsAndBonds"
      small
    >
      <template v-slot:head(symbol)>
        Label
        <QuestionCircleTooltip :id="'label-tooltip'"/>
        <b-tooltip target="label-tooltip" triggers="hover focus" delay="500">
          The green atoms/bonds are the ones that are marked. Clicking on each will highlight them in the molecule.
        </b-tooltip>
      </template>
      <template v-slot:head(scoreAdjustmentPercent)>
        Score Adjustment Percent
        <QuestionCircleTooltip :id="'score-adjustment-percent-tooltip'"/>
        <b-tooltip target="score-adjustment-percent-tooltip" triggers="hover focus" delay="500">
          The percent added for correct answers and removed for incorrect answers.
        </b-tooltip>
      </template>
      <template v-slot:cell(symbol)="data">
        <b-button :id="`label-${data.item.index}`"
                  :variant="hasMark(data.item) ? 'success' : ''"
                  :disabled="!hasMark(data.item)"
                  :style="!hasMark(data.item) ? 'pointer-events: none' : ''"
                  class="ml-2"
                  @click="initLoadStructure(data.item.index,data.item.structuralComponent)"
        >
          {{ data.item.structuralComponent === 'atom' ? data.item.symbol : data.item.type }}
        </b-button>
        <b-tooltip :target="`label-${data.item.index}`"
                   delay="500"
                   triggers="hover focus"
        >
          Use the Sketcher to view where you marked
          "{{ data.item.structuralComponent === 'atom' ? data.item.symbol : data.item.type }}".
        </b-tooltip>
      </template>
      <template v-slot:cell(scoreAdjustmentPercent)="data">
        <b-input-group style="width:150px" class="pb-1">
          <b-input-group-prepend>
            <b-button variant="info" size="sm" style="pointer-events: none">
              % Added
            </b-button>
          </b-input-group-prepend>
          <b-form-input v-model="data.item.correct"
                        size="sm"
                        required
                        type="text"
                        @input="removeError('correct', data.item.index)"
          />
        </b-input-group>
        <ErrorMessage v-if="questionForm.errors.errors
                        && questionForm.errors.errors.atoms_and_bonds
                        && questionForm.errors.errors.atoms_and_bonds[0]
                        && JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).specific
                        && JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).specific.find(item => +item.index === +data.item.index).correct"
                      :message="JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).specific.find(item => +item.index === +data.item.index).correct"
        />
        <b-input-group style="width:150px">
          <b-input-group-prepend>
            <b-button size="sm" variant="danger" style="pointer-events: none">
              % Removed
            </b-button>
          </b-input-group-prepend>
          <b-form-input v-model="data.item.incorrect"
                        size="sm"
                        required
                        type="text"
                        @input="removeError('incorrect', data.item.index)"
          />
        </b-input-group>
        <ErrorMessage v-if="questionForm.errors.errors
                        && questionForm.errors.errors.atoms_and_bonds
                        && questionForm.errors.errors.atoms_and_bonds[0]
                        && JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).specific
                        && JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).specific.find(item => +item.index === +data.item.index).incorrect"
                      :message="JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).specific.find(item => +item.index === +data.item.index).incorrect"
        />
      </template>
      <template v-slot:cell(feedback)="data">
        <ckeditor
          :id="`feedback-${data.item.index}`"
          v-model="data.item.feedback"
          tabindex="0"
          rows="3"
          :config="richEditorConfig"
          @namespaceloaded="onCKEditorNamespaceLoaded"
          @ready="handleFixCKEditor()"
        />
      </template>
    </b-table>
    <b-row v-if="qtiJson.partialCredit === 'inclusive'" class="font-weight-bold ml-2">
      Percent Added Total: {{ +atomsAndBonds.reduce((sum, item) => sum + +item.correct, 0).toFixed(1) }}%
    </b-row>
    <ErrorMessage v-if="questionForm.errors.errors
                    && questionForm.errors.errors.atoms_and_bonds
                    && questionForm.errors.errors.atoms_and_bonds[0]"
                  :message="JSON.parse(questionForm.errors.errors.atoms_and_bonds[0]).general"
    />
  </div>
</template>

<script>
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'
import ErrorMessage from '../ErrorMessage.vue'

export default {
  name: 'MultipleAnswersAdvanced',
  components: {
    ErrorMessage,
    ckeditor: CKEditor.component
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    questionForm: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    structureToLoad: {},
    richEditorConfig: {
      toolbar: [
        { name: 'clipboard', items: ['Cut', 'Copy', '-', 'Undo', 'Redo'] },
        {
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
        },
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['SpecialChar'] }
      ],
      removeButtons: '',
      resize_enabled: false,
      height: 100
    },
    atomsAndBonds: []
  }),
  computed: {
    atomsAndBondsFields () {
      const fields = [
        { key: 'symbol', label: 'Label' }
      ]

      if (this.qtiJson.partialCredit === 'inclusive') {
        fields.push({
          key: 'scoreAdjustmentPercent',
          label: 'Score Adjustment Percent'
        })
      }

      fields.push({
        key: 'feedback', label: 'Feedback'
      })

      return fields
    }
  },
  watch: {
    atomsAndBonds: {
      handler (newVal) {
        this.$emit('setAtomsAndBonds', newVal)
      },
      deep: true
    },
  },
  mounted () {
    let atomsAndBonds = []
    let index = 0
    let numOptions = 0
    for (const item of ['atoms', 'bonds']) {
      for (let i = 0; i < this.qtiJson.solutionStructure[item].length; i++) {
        numOptions++
      }
    }
    console.error(this.qtiJson.solutionStructure)

    for (const item of ['atoms', 'bonds']) {
      for (let i = 0; i < this.qtiJson.solutionStructure[item].length; i++) {
        let value = JSON.parse(JSON.stringify(this.qtiJson.solutionStructure[item][i]))
        value.structuralComponent = item.replace('s', '')
        value.structuralIndex = i
        value.index = index
        value.feedback = value.feedback ? value.feedback : ''
        value.correct = value.correct ? +value.correct : (100 / numOptions).toFixed(2)
        value.incorrect = value.incorrect ? +value.incorrect : 0
        index++
        atomsAndBonds.push(value)
      }
    }
    console.error(atomsAndBonds)
    this.atomsAndBonds = atomsAndBonds
    this.atomsAndBonds = atomsAndBonds.sort((a, b) => {
      const aHasMark = 'mark' in a
      const bHasMark = 'mark' in b

      if (aHasMark && !bHasMark) return -1
      if (!aHasMark && bHasMark) return 1
      return 0
    })
  },
  methods: {
    hasMark (obj) {
      return obj.mark !== null && obj.mark !== undefined
    },
    removeError (item, index) {
      if (this.questionForm.errors.errors.atoms_and_bonds) {
        let jsonErrors = JSON.parse(this.questionForm.errors.errors.atoms_and_bonds[0])
        jsonErrors.specific.find(item => +item.index === +index)[item] = ''
        jsonErrors.general = ''
        this.questionForm.errors.errors.atoms_and_bonds[0] = JSON.stringify(jsonErrors)
        this.$forceUpdate()
      }
    },
    initLoadStructure (index, type) {
      this.$bvModal.show('modal-single-marker-sketcher-viewer')
      this.structureToLoad.atoms = []
      this.structureToLoad.bonds = []
      for (const item of ['atoms', 'bonds']) {
        for (let i = 0; i < this.qtiJson.solutionStructure[item].length; i++) {
          let value = JSON.parse(JSON.stringify(this.qtiJson.solutionStructure[item][i]))
          console.error(item + 's', type)
          console.error(index, i)
          if (item === (type + 's') && index !== i) {
            delete value.mark
          }
          this.structureToLoad[item].push(value)
        }
      }
    },
    loadStructure () {
      document.getElementById('single-marker-sketcher-viewer').contentWindow.postMessage({
        method: 'load',
        structure: this.structureToLoad
      }, '*')
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    }
  }
}
</script>
