<template>
  <div>
    <b-modal id="modal-confirm-delete-rubric-category"
             title="Confirm Delete"
    >
      <p>Are you sure that you would like to delete:</p>
      <p class="text-center font-weight-bold">
        {{ activeRubricCategory.category }}
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-delete-rubric-category')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteRubricCategory()"
        >
          Yep, do it!
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-rubric-category"
             :title="isEdit ? `Update ${activeRubricCategory.category}` : 'New Category'"
             size="xl"
             no-close-on-backdrop
             :no-close-on-esc="false"
    >
      <b-form>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="category"
          label="Category"
        >
          <b-form-row>
            <b-col lg="10">
              <b-form-input
                id="category"
                v-model="activeRubricCategory.category"
                required
                type="text"
                :class="{ 'is-invalid': errors.category }"
                @keydown="errors.category = ''"
              />
            </b-col>
          </b-form-row>
          <ErrorMessage :message="errors.category" />
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="criteria"
          label="Criteria"
        >
          <b-form-row>
            <b-col lg="10">
              <b-textarea
                id="description"
                v-model="activeRubricCategory.criteria"
                required
                rows="8"
                type="text"
                :class="{ 'is-invalid': errors.criteria }"
                @keydown="errors.criteria = ''"
              />
            </b-col>
          </b-form-row>
          <ErrorMessage :message="errors.criteria" />
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="score"
        >
          <template v-slot:label>
            Points
          </template>
          <b-form-row>
            <b-col lg="2">
              <b-form-input
                id="score"
                v-model="activeRubricCategory.score"
                required
                type="text"
                :class="{ 'is-invalid': errors.score }"
                @keydown="errors.score = ''"
              />
            </b-col>
          </b-form-row>
          <ErrorMessage :message="errors.score" />
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <b-button
          size="sm"
          @click="$bvModal.hide('modal-rubric-category')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          @click="addRubric()"
        >
          {{ isEdit ? 'OK' : 'Add' }}
        </b-button>
      </template>
    </b-modal>
    <b-card header-html="<h1 class=&quot;h7&quot;>Rubric</h1>">
      <b-row v-if="!questionExistsInAnotherInstructorsAssignment" align-h="end" class="mb-4">
        <b-button variant="primary"
                  class="mr-1"
                  size="sm"
                  @click="isEdit=false;openNewCategoryModal()"
        >
          New Category
        </b-button>
      </b-row>
      <div v-if="rubricCategories.length">
        <b-alert variant="info" show>
          This report is worth {{ scoreSum }} points.
        </b-alert>
        <div
          :style="questionForm.errors
            && questionForm.errors.has('rubric_categories')
            ? 'border-color:red; border:1px solid #E6666F;'
            :''"
        >
          <table class="table table-striped mt-2"
                 aria-label="Rubric Categories"
          >
            <thead>
              <tr>
                <th scope="col" style="width:150px">
                  Category
                </th>
                <th scope="col">
                  Criteria
                </th>
                <th scope="col">
                  Points
                </th>
                <th v-if="!questionExistsInAnotherInstructorsAssignment" scope="col">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody is="draggable"
                   v-model="rubricCategories"
                   tag="tbody"
                   @end="saveNewOrder"
            >
              <tr v-for="item in rubricCategories" :key="item.id">
                <td>
                  <font-awesome-icon
                    v-if="!questionExistsInAnotherInstructorsAssignment"
                    :icon="barsIcon"
                  />
                  {{ item.category }}
                </td>
                <td>{{ item.criteria }}</td>
                <td>
                  {{ item.score }}
                </td>
                <td v-if="!questionExistsInAnotherInstructorsAssignment">
                  <b-icon icon="pencil"
                          class="text-muted"
                          style="cursor: pointer;"
                          :aria-label="`Edit ${item.category}`"
                          @click="initEditCategory( item)"
                  />
                  <b-icon icon="trash"
                          style="cursor: pointer;"
                          class="text-muted"
                          :aria-label="`Delete ${item.category}`"
                          @click="initDeleteCategory(item)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="questionForm.errors && questionForm.errors.has('rubric_categories')">
          <ErrorMessage :message="JSON.parse(questionForm.errors.get('rubric_categories')).category" />
          <ErrorMessage :message="JSON.parse(questionForm.errors.get('rubric_categories')).criteria" />
          <ErrorMessage :message="JSON.parse(questionForm.errors.get('rubric_categories')).score" />
        </div>
      </div>
      <div v-else>
        <b-alert variant="danger" show>
          There are currently no categories associated with this rubric.
        </b-alert>
      </div>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'
import draggable from 'vuedraggable'
import { faBars } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import ErrorMessage from '../ErrorMessage.vue'

export default {
  components: {
    ErrorMessage,
    draggable,
    FontAwesomeIcon
  },
  props: {
    questionExistsInAnotherInstructorsAssignment: {
      type: Boolean,
      default: false
    },
    questionForm: {
      type: Object,
      default: () => {
      }
    },
    questionId: {
      type: Number,
      default: 0
    },
    questionRevisionId: {
      type: Number,
      default: 0
    },
  },
  data: () => ({
    errors: {},
    gradingStyleOptions: [],
    barsIcon: faBars,
    rubricCategories: [],
    isLoading: true,
    allFormErrors: [],
    assignmentId: 0,
    isEdit: false,
    activeRubricCategory: {},
    scoreSum: 0
  }),
  computed: {
    isMe: () => window.config.isMe
  },
  watch: {
    rubricCategories: {
      handler (newVal) {
        this.scoreSum = newVal.reduce((sum, category) => {
          return sum + Number(category.score)
        }, 0)
      },
      deep: true
    }
  },
  mounted () {
    if (this.questionId) {
      this.getRubricsByQuestionIdAndRevisionId()
    }
  },
  methods: {
    saveNewOrder () {
      for (let i = 0; i < this.rubricCategories.length; i++) {
        this.rubricCategories[i].order = i + 1
        console.log(this.rubricCategories[i])
      }
      this.$forceUpdate()
      this.$emit('updateQuestionFormRubricCategories', this.rubricCategories)
    },
    computePercentSum () {
      this.scoreSum = this.rubricCategories.reduce((sum, category) => {
        return sum + Number(category.percent)
      }, 0) === 100
    },

    async deleteRubricCategory () {
      this.rubricCategories = this.rubricCategories.filter(item => item.order !== this.activeRubricCategory.order)
      this.saveNewOrder()
      this.$bvModal.hide('modal-confirm-delete-rubric-category')
    },
    initEditCategory (category) {
      this.activeRubricCategory = category
      this.isEdit = true
      this.$bvModal.show('modal-rubric-category')
    },
    initDeleteCategory (category) {
      this.activeRubricCategory = category
      this.$bvModal.show('modal-confirm-delete-rubric-category')
    },
    async addRubric () {
      this.errors = {}
      if (!this.activeRubricCategory.category) {
        this.errors.category = 'This field is required'
      }
      if (!this.activeRubricCategory.criteria) {
        this.errors.criteria = 'This field is required'
      }
      let pointsInput = +this.activeRubricCategory.score
      if (!pointsInput || isNaN(pointsInput) || pointsInput < 0) {
        this.errors.score = 'A valid number greater than 0 is required.'
      }

      if (Object.keys(this.errors).length) {
        return false
      }

      if (!this.isEdit) {
        this.activeRubricCategory.order = this.rubricCategories.length + 1
        this.rubricCategories.push(this.activeRubricCategory)
      }

      this.$bvModal.hide('modal-rubric-category')
      this.$emit('updateQuestionFormRubricCategories', this.rubricCategories)
    },
    openNewCategoryModal () {
      this.isEdit = false
      this.activeRubricCategory = { category: '', criteria: '', percent: '' }
      this.$bvModal.show('modal-rubric-category')
    },
    async getRubricsByQuestionIdAndRevisionId () {
      try {
        const { data } = await axios.get(`/api/questions/${this.questionId}/question-revision/${this.questionRevisionId}/rubric-categories`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.rubricCategories = data.rubric_categories
        this.computePercentSum()
        this.$emit('updateQuestionFormRubricCategories', this.rubricCategories)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
