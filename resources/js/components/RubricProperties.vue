<template>
  <div>
    <b-modal id="modal-confirm-delete-rubric-criterion"
             title="Delete Criterion"
             size="lg"
    >
      <p>Please confirm that you would like to delete the rubric criterion:</p>
      <p class="font-weight-bold">
        {{ rubricItemToDelete.criterion }}
      </p>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-delete-rubric-criterion')">
          Cancel
        </b-button>
        <b-button size="sm"
                  variant="danger"
                  @click="deleteRubricCriterion()"
        >
          Delete
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-rubric-properties"
             :title="modalTitle"
             size="xl"
             @hidden="$emit('hideRubricProperties')"
    >
      <b-alert type="info" :show="rubricPointsBreakdownExists">
        You have already saved the point breakdowns for at least one student. If you update this rubric, all breakdowns
        will be reset and you will
        have to enter in new scores for your students.
      </b-alert>
      <b-form-group
        v-if="rubricTemplateOptions.length"
        label-for="rubric_template"
        :label-cols-sm="isTemplate ? 2 : 3"
        :label-cols-lg="isTemplate ? 1 : 2"
        :label="isTemplate ? 'Template' : 'Load Rubric Template'"
        label-size="sm"
      >
        <b-form-row>
          <b-col lg="4">
            <b-form-select v-model="rubricTemplate"
                           :options="rubricTemplateOptions"
                           size="sm"
                           @change="getRubricTemplate($event)"
            />
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-show="!isTemplate"
        label-cols-sm="3"
        label-cols-lg="2"
        label="On save"
        label-size="sm"
        label-for="save_options"
      >
        <b-form-radio-group
          id="save_options"
          v-model="rubricTemplateSaveOption"
          class="mt-2"
        >
          <b-form-radio value="do not save as template">
            Do not save as template
          </b-form-radio>
          <b-form-radio value="save as new template">
            Save as new template
          </b-form-radio>
          <b-form-radio v-if="rubricTemplate" value="update existing template">
            Update existing template
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label-size="sm"
        label-for="rubric_shown"
      >
        <template #label>
          Rubric Shown
          <QuestionCircleTooltip
            id="rubric-shown-tooltip"
          />
          <b-tooltip target="rubric-shown-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            If you choose `No` then your students will not see the breakdown of the score.
          </b-tooltip>
        </template>
        <b-form-radio-group
          id="rubric_shown"
          v-model="rubricShown"
          class="mt-2"
        >
          <b-form-radio :value="true">
            Yes
          </b-form-radio>
          <b-form-radio :value="false">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-if="isTemplate || ['save as new template','update existing template'].includes(rubricTemplateSaveOption)"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="template_name"
        label="Template Name*"
      >
        <b-form-row>
          <b-form-input
            id="name"
            v-model="name"
            required
            type="text"
            :class="{ 'is-invalid': errors.name}"
            @keydown="errors.name = ''"
          />
          <ErrorMessage v-if="errors.name" :message="errors.name[0]"/>
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-if="isTemplate || ['save as new template','update existing template'].includes(rubricTemplateSaveOption)"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="description"
        label="Description*"
      >
        <b-form-row>
          <b-form-textarea
            id="description"
            v-model="description"
            required
            type="text"
            :class="{ 'is-invalid': errors.description}"
            @keydown="errors.description = ''"
          />
          <ErrorMessage v-if="errors.description" :message="errors.description[0]"/>
        </b-form-row>
      </b-form-group>
      <div v-if="rubricItems.length">
        <RequiredText/>

        <table class="table table-striped small" aria-label="Rubric Criteria">
          <thead>
          <tr>
            <th scope="col" style="width:15px"/>
            <th scope="col">
              Title*
            </th>
            <th scope="col">
              Criteria*
            </th>
            <th scope="col">
              Description (Optional)
            </th>
            <th scope="col" style="width:100px">
              <b-form-radio
                v-model="scoreInputType"
                name="score_input_type"
                value="points"
                @change="resetScoreInputType('percentage')"
              >
                Points
              </b-form-radio>
              <b-form-radio
                v-model="scoreInputType"
                name="score_input_type"
                value="percentage"
                @change="resetScoreInputType('points')"
              >
                Percentage
              </b-form-radio>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr
            v-for="( rubricItem,rubricItemIndex) in rubricItems"
            :key="`rubric-criterion-${rubricItemIndex}`"
          >
            <td>
              <b-icon icon="trash" @click="confirmDeleteCriterion(rubricItem)"/>
            </td>
            <td>
              <b-form-input
                v-model="rubricItem.title"
                type="text"
                placeholder=""
                required
                style="width:100%"
                size="sm"
                :class="{ 'is-invalid': errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].title !== 'passes'}"
                @keydown="errors.rubric_items && errors.rubric_items[rubricItemIndex] ? errors.rubric_items[rubricItemIndex].title = 'passes': ''"
              />
              <ErrorMessage
                v-if="errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].title !== 'passes'"
                :message="errors.rubric_items[rubricItemIndex].title"
              />
            </td>
            <td>
              <b-form-textarea
                v-model="rubricItem.criterion"
                type="text"
                placeholder=""
                rows="2"
                required
                style="width:100%"
                size="sm"
                :class="{ 'is-invalid': errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].criterion !== 'passes'}"
                @keydown="errors.rubric_items && errors.rubric_items[rubricItemIndex] ? errors.rubric_items[rubricItemIndex].criterion = 'passes': ''"
              />
              <ErrorMessage
                v-if="errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].criterion !== 'passes'"
                :message="errors.rubric_items[rubricItemIndex].criterion"
              />
            </td>
            <td>
              <b-form-textarea
                v-model="rubricItem.description"
                type="text"
                placeholder=""
                rows="2"
                required
                size="sm"
                style="width:100%"
              />
            </td>
            <td>
              <div v-show="scoreInputType === 'points'">
                <b-form-input
                  v-model="rubricItem.points"
                  type="text"
                  size="sm"
                  style="width:60px"
                  placeholder=""
                  required
                  :class="{ 'is-invalid': errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].points !== 'passes'}"
                  @keydown="errors.rubric_items && errors.rubric_items[rubricItemIndex].points ?  'passes' : ''"
                />
                <ErrorMessage
                  v-if="errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].points !== 'passes'"
                  :message="errors.rubric_items[rubricItemIndex].points"
                />
              </div>
              <div v-show="scoreInputType === 'percentage'">
                <div class="d-inline-flex">
                  <b-form-input
                    v-model="rubricItem.percentage"
                    type="text"
                    size="sm"
                    style="width:60px"
                    placeholder=""
                    required
                    :class="{ 'is-invalid': errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].percentage !== 'passes'}"
                    @keydown="errors.rubric_items && errors.rubric_items[rubricItemIndex].percentage ? 'passes' : ''"
                  />
                  <span class="pl-1 pt-1">%</span></div>
                <ErrorMessage
                  v-if="errors.rubric_items && errors.rubric_items[rubricItemIndex] && errors.rubric_items[rubricItemIndex].percentage !== 'passes'"
                  :message="errors.rubric_items[rubricItemIndex].percentage"
                />
              </div>
            </td>
          </tr>
          <tr v-if="rubricItems.length && isTemplate">
            <td/>
            <td/>
            <td/>
            <td/>
            <td class="font-weight-bold">Total: <span
              :class="scoreInputType === 'percentage' ? (getRunningTotal() === 100 ? 'text-success' : 'text-danger') : ''"
            >{{ getRunningTotal() }}<span v-show="scoreInputType === 'percentage'">%</span></span></td>
          </tr>
          </tbody>
        </table>
      </div>
      <p>
        <b-button variant="info" class="mr-1"
                  size="sm" @click="addRubricItemRow"
        >
          Add Criterion
        </b-button>
      </p>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="$bvModal.hide('modal-rubric-properties')">
          Cancel
        </b-button>
        <b-button size="sm"
                  variant="primary"
                  :disabled="!rubricItems.length"
                  @click="handleSubmitRubricInfo()"
        >
          Save
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import ErrorMessage from './ErrorMessage.vue'

export default {
  name: 'RubricProperties',
  components: {
    ErrorMessage
  },
  props: {
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    },
    questionPoints: {
      type: Number,
      default: 0
    },
    isTemplate: {
      type: Boolean,
      default: false
    },
    isEdit: {
      type: Boolean,
      default: false
    },
    showRubricProperties: {
      type: Boolean,
      default: false
    },
    rubricInfo: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    rubricShown: true,
    initialScoreInputType: '',
    scoreInputType: 'points',
    rubricPointsBreakdownExists: false,
    rubricItemToDelete: {},
    rubricTemplateId: 0,
    rubricTemplateSaveOption: 'do not save as template',
    rubricTemplate: {},
    rubricTemplateOptions: [],
    templates: [],
    modalTitle: '',
    errors: [],
    rubricItems: [],
    name: '',
    description: '',
    currentOrderedRubricItems: []
  }),
  async mounted () {
    console.error(this.rubricInfo)
    if (this.isTemplate) {
      const action = this.isEdit ? 'Edit' : 'Create'
      this.modalTitle = `${action} Rubric Template`
    } else {
      await this.getRubricTemplates()
      this.modalTitle = 'Question Rubric'
    }
    if (this.isEdit) {
      if (!this.isTemplate && this.assignmentId && this.questionId) {
        await this.getRubricPointsBreakdowns()
      }
      this.name = this.rubricInfo.name
      this.description = this.rubricInfo.description
      const rubricInfo = JSON.parse(this.rubricInfo.rubric)
      const rubricItems = rubricInfo.rubric_items
      this.initialScoreInputType = rubricInfo.score_input_type
      this.scoreInputType = rubricInfo.score_input_type
      for (let i = 0; i < rubricItems.length; i++) {
        if (!rubricItems[i].hasOwnProperty('points')) {
          rubricItems[i].points = ''
        }
        if (!rubricItems[i].hasOwnProperty('percentage')) {
          rubricItems[i].percentage = ''
        }
      }
      this.rubricItems = rubricItems
      this.scalePoints()
    }
    const modal = this.$bvModal
    this.showRubricProperties ? modal.show('modal-rubric-properties') : modal.hide('modal-rubric-properties')
  },
  methods: {
    resetScoreInputType (typeToReset) {
      if (!this.isEdit || typeToReset !== this.initialScoreInputType) {
        for (let i = 0; i < this.rubricItems.length; i++) {
          this.rubricItems[i][typeToReset] = ''
        }
      }
    },
    getRunningTotal () {
      console.error(this.rubricItems)
      return this.rubricItems.reduce((sum, item) => sum + +item[this.scoreInputType], 0)
    },
    async getRubricPointsBreakdowns () {
      try {
        const { data } = await axios.get(`/api/rubric-points-breakdown/assignment/${this.assignmentId}/question/${this.questionId}/exists`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.rubricPointsBreakdownExists = data.rubric_points_breakdown_exists
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    deleteRubricCriterion () {
      this.rubricItems = this.rubricItems.filter(item => item.criterion !== this.rubricItemToDelete.criterion)
      this.scalePoints()
      this.$bvModal.hide('modal-confirm-delete-rubric-criterion')
      this.$noty.info('The criterion has been removed.')
    },
    confirmDeleteCriterion (rubricItem) {
      this.rubricItemToDelete = rubricItem
      this.$bvModal.show('modal-confirm-delete-rubric-criterion')
    },
    scalePoints () {
      if (this.questionPoints) {
        let totalPoints = 0
        for (let i = 0; i < this.rubricItems.length; i++) {
          totalPoints += +this.rubricItems[i].points
        }
        for (let i = 0; i < this.rubricItems.length; i++) {
          const points = (this.rubricItems[i].points / totalPoints) * this.questionPoints
          this.rubricItems[i].points = +points.toFixed(4)
        }
      }
    },
    getRubricTemplate (rubricTemplateId) {
      if (!rubricTemplateId) {
        this.rubricItems = []
        this.name = ''
        this.description = ''
      }
      const template = this.rubricTemplateOptions.find(item => item.value === rubricTemplateId)

      const rubricInfo = JSON.parse(template.rubric) || {}
      if (rubricInfo.hasOwnProperty('rubric_items')) {
        this.scoreInputType = rubricInfo.score_input_type
        this.rubricItems = rubricInfo.rubric_items
      }
      this.name = template.name
      this.description = template.description
      this.rubricTemplateId = rubricTemplateId
      this.scalePoints()
    },
    async getRubricTemplates () {
      try {
        const { data } = await axios.get('/api/rubric-templates')
        if (data.rubric_templates) {
          this.rubricTemplate = null
          this.rubricTemplateOptions = [{ text: 'Optionally choose a template', value: null, rubric: null }]
          for (let i = 0; i < data.rubric_templates.length; i++) {
            const template = data.rubric_templates[i]
            this.rubricTemplateOptions.push({
              text: template.name,
              value: template.id,
              rubric: template.rubric,
              name: template.name,
              description: template.description
            })
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleSubmitRubricInfo () {
      const runningTotal = this.getRunningTotal()
      if (this.scoreInputType === 'percentage' && runningTotal !== 100) {
        this.$noty.error(`The total of your percentages should sum to 100%; they currently only sum to ${runningTotal}%.`)
        return
      }
      try {
        const saveAsTemplate = ['save as new template', 'update existing template'].includes(this.rubricTemplateSaveOption)
        if (this.isTemplate || saveAsTemplate) {
          const rubricData = {
            name: this.name,
            description: this.description,
            rubric_items: this.rubricItems,
            save_as_template: true,
            assignment_id: this.assignmentId,
            question_id: this.questionId,
            score_input_type: this.scoreInputType,
            rubric_shown: this.rubricShown
          }
          const action = this.rubricTemplateSaveOption === 'update existing template' ? 'patch' : 'post'
          let rubricTemplateId
          if (this.isEdit) {
            rubricTemplateId = saveAsTemplate ? this.rubricTemplateId : this.rubricInfo.id
          }
          const url = this.isEdit ? `/api/rubric-templates/${rubricTemplateId}` : '/api/rubric-templates'
          const { data } = await axios[action](url, rubricData)
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            this.$bvModal.hide('modal-rubric-properties')
            saveAsTemplate
              ? this.$emit('setRubric', JSON.stringify({
                rubric_items: this.rubricItems,
                score_input_type: this.scoreInputType
              }))
              : this.$emit('reloadRubricTemplates')
            if (this.assignmentId) {
              this.$emit('reloadRubricAndRubricPointsBreakdown')
            }
          }
        } else {
          const { data } = await axios.post(`/api/rubric-templates/validate-rubric-items`,
            {
              save_as_template: false,
              rubric_items: this.rubricItems,
              score_input_type: this.scoreInputType,
              rubric_shown: this.rubricShown
            })
          if (data.type === 'success') {
            this.$emit('setRubric', JSON.stringify({
              rubric_items: this.rubricItems,
              score_input_type: this.scoreInputType
            }))
          }
          if (this.assignmentId) {
            this.$emit('reloadRubricAndRubricPointsBreakdown')
          }
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.errors = error.response.data.errors
          if (this.errors.rubric_items) {
            this.errors.rubric_items = JSON.parse(this.errors.rubric_items)
          }
        }
      }
    },
    addRubricItemRow () {
      this.rubricItems.push({ title: '', criterion: '', description: '', points: '', percentage: '' })
    }
  }
}
</script>

<style scoped>

</style>
