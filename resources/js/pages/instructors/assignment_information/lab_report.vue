<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-rubric-form" />
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-lab-report-form" />
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
    >
      <b-form>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="category"
          label="Catetory"
        >
          <b-form-row>
            <b-col lg="10">
              <b-form-input
                id="category"
                v-model="rubricCategoryForm.category"
                required
                type="text"
                :class="{ 'is-invalid': rubricCategoryForm.errors.has('category') }"
                @keydown="rubricCategoryForm.errors.clear('category')"
              />
              <has-error :form="rubricCategoryForm" field="category" />
            </b-col>
          </b-form-row>
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
                v-model="rubricCategoryForm.criteria"
                required
                rows="8"
                type="text"
                :class="{ 'is-invalid': rubricCategoryForm.errors.has('criteria') }"
                @keydown="rubricCategoryForm.errors.clear('criteria')"
              />
              <has-error :form="rubricCategoryForm" field="criteria" />
            </b-col>
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="percent"
          label="Percent"
        >
          <b-form-row>
            <b-col lg="2">
              <b-form-input
                id="percent"
                v-model="rubricCategoryForm.percent"
                required
                type="text"
                :class="{ 'is-invalid': rubricCategoryForm.errors.has('percent') }"
                @keydown="rubricCategoryForm.errors.clear('percent')"
              />
              <has-error :form="rubricCategoryForm" field="percent" />
            </b-col>
          </b-form-row>
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

          @click="saveRubric()"
        >
          Save
        </b-button>
      </template>
    </b-modal>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <PageTitle title="Lab Report" />
      To do:
      <ul>
        <li>What to do about the comments that students can see? nothing showing currently</li>
        <li>Show/hide rubric</li>
        <li>Allow users to delete the purpose</li>
        <li>Delete category if there are submissions</li>
        <li>Update category if there are submissions --- will have to compute</li>
        <li>Import rubrics?</li>
        <li>alpha/beta</li>
        <li>the student error will happen if there's a php error as well (need to fix for only if it's empty)</li>
        <li>Copy assignment</li>
        <li>Copy course</li>
        <li>Import assignment</li>
      </ul>
      <b-card header-html="<h1 class=&quot;h7&quot;>Purpose</h1>" class="mb-3">
        <div class="mb-3">
          Please specify the purpose of the lab so the AI has some context in which to grade.
        </div>
        <b-form-group>
          <b-form-row>
            <b-textarea
              id="purpose"
              v-model="labReportForm.purpose"
              required
              rows="3"
              type="text"
              :class="{ 'is-invalid': labReportForm.errors.has('purpose') }"
              @keydown="labReportForm.errors.clear('purpose')"
            />
            <has-error :form="labReportForm" field="purpose" />
          </b-form-row>
          <div class="mt-3">
            <b-button
              variant="primary"
              size="sm"
              class="float-right"
              @click="updatePurpose"
            >
              Save
            </b-button>
          </div>
        </b-form-group>
      </b-card>
      <b-card header-html="<h1 class=&quot;h7&quot;>Rubric</h1>">
        <b-row align-h="end" class="mb-4">
          <b-button variant="primary"
                    class="mr-1"
                    size="sm"
                    @click="openNewCategoryModal()"
          >
            New Category
          </b-button>
        </b-row>
        <div v-if="!isLoading">
          <div v-if="rubricCategories.length">
            <div v-if="!percentsSumTo100">
              <b-alert variant="danger" show>
                The percent column should sum to 100.
              </b-alert>
            </div>
            <table class=" table table-striped mt-2"
                   aria-label="Rubric Categories"
            >
              <thead>
                <tr>
                  <th scope="col">
                    Category
                  </th>
                  <th scope="col">
                    Criteria
                  </th>
                  <th scope="col">
                    Percent
                  </th>
                  <th scope="col">
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
                      :icon="barsIcon"
                    />
                    {{ item.category }}
                  </td>
                  <td>{{ item.criteria }}</td>
                  <td>{{ item.percent }}%</td>
                  <td>
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
          <div v-else>
            <b-alert variant="info" show>
              There is currently no categories associated with this rubric.
            </b-alert>
          </div>
        </div>
      </b-card>
    </div>
  </div>
</template>

<script>
import AllFormErrors from '~/components/AllFormErrors.vue'
import axios from 'axios'
import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import draggable from 'vuedraggable'
import { faBars } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const defaultRubricForm = { category: '', criteria: '', percent: '', assignment_id: 0 }
export default {
  components: {
    AllFormErrors,
    Loading,
    draggable,
    FontAwesomeIcon
  },
  data: () => ({
    rubricCategoriesFields: [
      'category',
      'criteria',
      'percent',
      'actions'
    ],
    labReportForm: new Form({ purpose: '' }),
    barsIcon: faBars,
    rubricCategories: [],
    isLoading: true,
    allFormErrors: [],
    assignmentId: 0,
    isEdit: false,
    rubricCategoryForm: new Form(defaultRubricForm),
    activeRubricCategory: {},
    percentsSumTo100: true
  }),
  computed: {
    isMe: () => window.config.isMe
  },
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
    this.getLabReportPurposeByAssignment()
    this.getRubricsByAssignment()
  },
  methods: {
    async updatePurpose () {
      try {
        const { data } = await this.labReportForm.patch(`/api/assignments/${this.assignmentId}/purpose`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.labReportForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-lab-report-form')
        }
      }
    },
    computePercentSum () {
      this.percentsSumTo100 = this.rubricCategories.reduce((sum, category) => {
        return sum + Number(category.percent)
      }, 0) === 100
    },
    async saveNewOrder () {
      let orderedRubricCategories = []
      for (let i = 0; i < this.rubricCategories.length; i++) {
        orderedRubricCategories.push(this.rubricCategories[i].id)
      }
      try {
        const { data } = await axios.patch(`/api/rubric-categories/${this.assignmentId}/order`, { ordered_rubric_categories: orderedRubricCategories })
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async deleteRubricCategory () {
      try {
        const { data } = await axios.delete(`/api/rubric-categories/${this.activeRubricCategory.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'info') {
          this.$bvModal.hide('modal-confirm-delete-rubric-category')
        }
        await this.getRubricsByAssignment()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initEditCategory (category) {
      this.activeRubricCategory = category
      this.isEdit = true
      this.rubricCategoryForm = new Form(category)
      this.$bvModal.show('modal-rubric-category')
    },
    initDeleteCategory (category) {
      this.activeRubricCategory = category
      this.$bvModal.show('modal-confirm-delete-rubric-category')
    },
    async saveRubric () {
      try {
        this.rubricCategoryForm.assignment_id = this.assignmentId
        const { data } = this.isEdit
          ? await this.rubricCategoryForm.patch(`/api/rubric-categories/${this.activeRubricCategory.id}`)
          : await this.rubricCategoryForm.post('/api/rubric-categories')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$bvModal.hide('modal-rubric-category')
        }
        await this.getRubricsByAssignment()
      } catch (error) {
        if (!error.message.includes('422')) {
          this.$noty.error(error.message)
        }
      }
    },
    openNewCategoryModal () {
      this.isEdit = false
      this.rubricCategoryForm = new Form(defaultRubricForm)
      this.$bvModal.show('modal-rubric-category')
    },
    async getLabReportPurposeByAssignment () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/purpose`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let purpose = data.purpose ? data.purpose : ''
        this.labReportForm = new Form({ purpose: purpose })
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getRubricsByAssignment () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/rubric-categories`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.rubricCategories = data.rubric_categories
        this.computePercentSum()
        console.log(this.rubrics)
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
