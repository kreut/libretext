<template>
  <div>
    <b-modal
      id="view-questions-in-my-questions"
      title=""
      :size="questionToViewHasSolutionHtml ? 'xl' : 'lg'"
      ok-title="OK"
      ok-only
    >
      <ViewQuestions :key="`view-selected-questions-clicked-${numViewSelectedQuestionsClicked}`"
                     :question-ids-to-view="selectedQuestionIds"
                     :show-solutions="true"
                     @questionToViewSet="setQuestionToView"
      />
    </b-modal>
    <b-modal
      id="modal-edit-question"
      :title="`Edit Question &quot;${questionToEdit.title}&quot;`"
      :no-close-on-esc="true"
      size="xl"
      hide-footer
      @shown="updateModalToggleIndex('modal-edit-question')"
    >
      <CreateQuestion :key="`question-to-edit-${questionToEdit.id}`"
                      :question-to-edit="questionToEdit"
                      :modal-id="'my-questions-question-to-view-questions-editor'"
                      :parent-get-my-questions="getMyQuestions"
                      :question-exists-in-own-assignment="questionExistsInOwnAssignment"
                      :question-exists-in-another-instructors-assignment="questionExistsInAnotherInstructorsAssignment"
      />
    </b-modal>
    <b-modal
      id="modal-confirm-delete-questions"
      :title="`Confirm Delete Question${questionsToDelete.length === 1 ? '' : 's'}`"
      :no-close-on-esc="true"
      size="lg"
    >
      <b-table
        :key="deletedKey"
        aria-label="Questions To Delete"
        striped
        hover
        responsive
        :no-border-collapse="true"
        :fields="questionsToDeleteFields"
        :items="questionsToDelete"
      >
        <template v-slot:cell(deleted_status)="data">
          <span v-html="data.item.deleted_status"/>
        </template>
      </b-table>
      <template #modal-footer>
        <div v-if="!deletingQuestions" class="float-right">
          <b-button
            size="sm"
            @click="$bvModal.hide('modal-confirm-delete-questions')"
          >
            Cancel
          </b-button>
          <b-button
            size="sm"
            variant="danger"
            @click="handleDeleteQuestions"
          >
            Delete Question<span v-if="questionsToDelete.length >1">s</span>
          </b-button>
        </div>
        <div class="float-right">
          <span v-if="deletingQuestions">Deleting {{ deletingIndex }} of  {{ questionsToDelete.length }}</span>
          <b-button
            v-if="deletingQuestions"
            size="sm"
            class="ml-2"
            :disabled="!deletedQuestions"
            @click="$bvModal.hide('modal-confirm-delete-questions')"
          >
            Close
          </b-button>
        </div>
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
      <div v-if="!isLoading">
        <b-container v-if="myQuestions.length" fluid>
          <!-- User Interface controls -->
          <b-row>
            <b-col sm="5" md="6" class="my-1">
              <b-form-group
                label="Per page"
                label-for="per-page-select"
                label-cols-sm="6"
                label-cols-md="4"
                label-cols-lg="3"
                label-align-sm="right"
                label-size="sm"
                class="mb-0"
              >
                <b-form-select
                  id="per-page-select"
                  v-model="perPage"
                  style="width:100px"
                  :options="pageOptions"
                  size="sm"
                />
              </b-form-group>
            </b-col>
            <b-col sm="7" md="6" class="my-1">
              <b-pagination
                v-model="currentPage"
                :total-rows="totalRows"
                :per-page="perPage"
                align="center"
                first-number
                last-number
                class="my-0"
              />
            </b-col>
            <b-col lg="6" class="my-1">
              <b-form-group
                label="Filter"
                label-for="filter-input"
                label-cols-sm="3"
                label-align-sm="right"
                label-size="sm"
                class="mb-0"
              >
                <b-input-group size="sm">
                  <b-form-input
                    id="filter-input"
                    v-model="filter"
                    type="search"
                    placeholder="Type to Search"
                  />

                  <b-input-group-append>
                    <b-button :disabled="!filter" @click="filter = ''">
                      Clear
                    </b-button>
                  </b-input-group-append>
                </b-input-group>
              </b-form-group>
            </b-col>
          </b-row>
          <b-row>
            <b-col lg="6" class="my-1">
              <b-button variant="primary"
                        :class="{ 'disabled': !selectedQuestionIds.length}"
                        :aria-disabled="!selectedQuestionIds.length"
                        size="sm"
                        @click="!selectedQuestionIds.length ? '' : viewSelectedQuestions()"
              >
                View Selected
              </b-button>
              <b-button variant="danger"
                        :class="{ 'disabled': !selectedQuestionIds.length}"
                        :aria-disabled="!selectedQuestionIds.length"
                        size="sm"
                        @click="!selectedQuestionIds.length ? '' : initDeleteQuestions(selectedQuestionIds)"
              >
                Delete Selected
              </b-button>
            </b-col>
          </b-row>
          <b-table
            id="my_questions"
            aria-label="My Questions"
            striped
            hover
            responsive
            :no-border-collapse="true"
            :fields="fields"
            :items="myQuestions"
            :per-page="perPage"
            :current-page="currentPage"
            :filter="filter"
            @filtered="onFiltered"
          >
            <template #head(title)="data">
              <input id="select_all" type="checkbox" @click="selectAll"> Title
            </template>
            <template v-slot:cell(title)="data">
              <input v-model="selectedQuestionIds" type="checkbox" :value="data.item.id" class="selected-question-id">
              <a href=""
                 @click.prevent="selectedQuestionIds=[data.item.id];viewSelectedQuestions()"
              >{{ data.item.title }}</a>
              <span v-if="data.item.technology === 'h5p'">
                <a :href="`https://studio.libretexts.org/h5p/${data.item.technology_id}`" target="_blank">
                 <font-awesome-icon :icon="externalLinkIcon"/>
                </a>
              </span>
            </template>
            <template v-slot:cell(page_id)="data">
              {{ data.item.page_id }}
              <a :id="getTooltipTarget('copy',data.item.page_id)"
                 href=""
                 class="pr-1"
                 :aria-label="`Copy Question ID for ${data.item.title}`"
                 @click.prevent="doCopy(data.item.page_id)"
              >
                <font-awesome-icon :icon="copyIcon"/>
              </a>
            </template>
            <template v-slot:cell(updated_at)="data">
              {{ $moment(data.item.updated_at, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY h:mm A') }}
            </template>
            <template v-slot:cell(actions)="data">
              <b-tooltip :target="getTooltipTarget('view',data.item.id)"
                         delay="500"
                         triggers="hover focus"
              >
                View the question
              </b-tooltip>
              <b-tooltip :target="getTooltipTarget('edit',data.item.id)"
                         delay="500"
                         triggers="hover focus"
              >
                Edit the question
              </b-tooltip>
              <a :id="getTooltipTarget('edit',data.item.id)"
                 href=""
                 class="pr-1"
                 @click.prevent="editQuestion(data.item)"
              >
                <b-icon class="text-muted"
                        icon="pencil"
                        :aria-label="`Edit ${data.item.title}`"
                />
              </a>
              <b-tooltip :target="getTooltipTarget('delete',data.item.id)"
                         delay="500"
                         triggers="hover focus"
              >
                Delete the question
              </b-tooltip>

              <a :id="getTooltipTarget('delete',data.item.id)"
                 href=""
                 class="pr-1"
                 @click.prevent="initDeleteQuestions([data.item.id])"
              >
                <b-icon class="text-muted"
                        icon="trash"
                        :aria-label="`Delete ${data.item.title}`"
                />
              </a>
            </template>
          </b-table>
        </b-container>
      </div>
      <b-alert :show="!myQuestions.length && !isLoading" class="pt-2">
        <span class="font-weight-bold">
          You have not created any questions.
        </span>
      </b-alert>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import CreateQuestion from './CreateQuestion'
import ViewQuestions from '~/components/ViewQuestions'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { faExternalLinkAlt } from '@fortawesome/free-solid-svg-icons'
import { doCopy } from '~/helpers/Questions'
import { updateModalToggleIndex } from '~/helpers/accessibility/fixCKEditor'

export default {
  name: 'MyQuestions',
  components: {
    Loading,
    CreateQuestion,
    ViewQuestions,
    FontAwesomeIcon
  },
  props: {
    questionId: {
      type: Number,
      default: 0
    }
  },
  middleware: 'auth',
  data: () => ({
      questionToViewHasSolutionHtml: false,
      questionToViewTitle: '',
      copyIcon: faCopy,
      externalLinkIcon: faExternalLinkAlt,
      questionExistsInOwnAssignment: false,
      questionExistsInAnotherInstructorsAssignment: false,
      deletedQuestions: false,
      deletingIndex: 1,
      deletingQuestions: false,
      numViewSelectedQuestionsClicked: 0,
      questionToEdit: {},
      deletedKey: 0,
      questionsToDelete: [],
      filteredItems: [],
      selectedQuestionIds: [],
      totalRows: 0,
      filter: null,
      currentPage: 1,
      pageOptions: [10, 50, 100, 500, { value: 10000, text: 'Show All' }],
      perPage: 10,
      question: {},
      myQuestions: [],
      isLoading: true,
      questionsToDeleteFields: [
        {
          key: 'title',
          isRowHeader: true
        },
        'technology',
        {
          key: 'tags',
          formatter: value => {
            return value.join(', ')
          }
        },
        {
          key: 'deleted_status',
          label: 'Status'
        }
      ],
      fields: [
        {
          key: 'title',
          isRowHeader: true,
          sortable: true,
          sortDirection: 'desc'
        },
        {
          key: 'question_type',
          label: 'Type',
          formatter: value => {
            return value.charAt(0).toUpperCase() + value.slice(1)
          }
        },
        {
          key: 'page_id',
          sortable: true,
          label: 'Question ID'
        },
        {
          key: 'technology',
          sortable: true,
          sortDirection: 'desc'
        },
        {
          key: 'tags',
          formatter: value => {
            return value.join(', ')
          },
          sortable: true,
          sortDirection: 'desc'
        },
        {
          key: 'public',
          formatter: value => {
            return parseInt(value) === 1 ? 'Yes' : 'No'
          }
        },
        {
          key: 'updated_at',
          label: 'Last Updated',
          sortable: true,
          sortDirection: 'desc'
        },
        {
          key: 'actions',
        thStyle: { width: '95px' }
      }
      ]
    }
  ),
  created () {
    this.updateModalToggleIndex = updateModalToggleIndex
  },
  mounted () {
    this.doCopy = doCopy
    this.getMyQuestions()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    updateModalTitle () {
      if (document.getElementById('view-questions-in-my-questions___BV_modal_title')) {
        document.getElementById('view-questions-in-my-questions___BV_modal_title').innerHTML = this.questionToViewTitle
        console.log('updated title')
      }
    },
    setQuestionToView (questionToView) {
      this.questionToViewHasSolutionHtml = questionToView.solution_html !== null
      this.questionToViewTitle = questionToView.title
      // pretty bad below.  Better way to wait until the title exists?
      setTimeout(this.updateModalTitle, 100)
      setTimeout(this.updateModalTitle, 500)
      setTimeout(this.updateModalTitle, 1000)
    },
    async editQuestion (questionToEdit) {
      this.questionToEdit = questionToEdit
      await this.getQuestionAssignmentStatus()
      if (this.questionToEdit.non_technology) {
        await this.getNonTechnologyText()
      }
      this.$bvModal.show('modal-edit-question')
    },
    async getQuestionAssignmentStatus () {
      try {
        const { data } = await axios.get(`/api/questions/${this.questionToEdit.id}/assignment-status`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.questionExistsInOwnAssignment = data.question_exists_in_own_assignment
          this.questionExistsInAnotherInstructorsAssignment = data.question_exists_in_another_instructors_assignment
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getNonTechnologyText () {
      try {
        const { data } = await axios.get(`/api/get-locally-saved-page-contents/adapt/${this.questionToEdit.page_id}`)
        this.questionToEdit.non_technology_text = data
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    selectAll () {
      this.selectedQuestionIds = []
      let checkboxes = document.getElementsByClassName('selected-question-id')
      if (document.getElementById('select_all').checked) {
        for (let checkbox of checkboxes) {
          this.selectedQuestionIds.push(parseInt(checkbox.value))
        }
      }
    },
    viewSelectedQuestions () {
      this.numViewSelectedQuestionsClicked++
      this.$bvModal.show('view-questions-in-my-questions')
    },
    initDeleteQuestions (questionIds) {
      this.deletingQuestions = false
      this.deletedQuestions = false
      this.$bvModal.show('modal-confirm-delete-questions')
      console.log(questionIds)
      this.questionsToDelete = this.myQuestions.filter(question => questionIds.includes(question.id))
      for (let i = 0; i < this.questionsToDelete.length; i++) {
        this.questionsToDelete[i].deleted_status = 'Pending'
      }
    },
    onFiltered (filteredItems) {
      // Trigger pagination to update the number of buttons/pages due to filtering
      this.totalRows = filteredItems.length
      this.filteredItems = filteredItems
      this.selectedQuestionIds = []
      this.currentPage = 1
    },
    async handleDeleteQuestions () {
      this.deletedKey = 0
      let deletedQuestionIds = []
      this.deletedQuestions = false
      this.deletingQuestions = true
      this.deletingIndex = 1
      for (let i = 0; i < this.questionsToDelete.length; i++) {
        let questionToDelete = this.questionsToDelete[i]
        this.deletingIndex = i + 1
        try {
          const { data } = await axios.delete(`/api/questions/${questionToDelete.id}`)
          this.questionsToDelete[i].deleted_status = data.type !== 'error'
            ? '<span class="text-success">Success</span>'
            : `<span class="text-danger">Error: ${data.message}</span>`
          this.deletedKey = i + 1
          if (data.type !== 'error') {
            deletedQuestionIds.push(questionToDelete.id)
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
      this.myQuestions = this.myQuestions.filter(question => !deletedQuestionIds.includes(question.id))
      this.selectedQuestionIds = []
      this.deletedQuestions = true
    },
    async getMyQuestions () {
      try {
        const { data } = await axios.get('/api/questions')

        if (data.type === 'success') {
          this.myQuestions = data.my_questions
          this.totalRows = this.myQuestions.length
          this.filteredItems = this.myQuestions

          let questionToEdit = {}
          if (this.questionId !== 0) {
            for (let i = 0; i < this.myQuestions.length; i++) {
              if (this.myQuestions[i].id === this.questionId) {
                questionToEdit = this.myQuestions[i]
              }
            }
            if (!Object.keys(questionToEdit).length) {
              this.$noty.info(`Question ID ${this.questionId} does not seem to be in your list of questions.`)
            }
            this.questionId = 0 // just do it once or the modal will keep popping up
          }
          if (Object.keys(questionToEdit).length) {
            await this.editQuestion(questionToEdit)
          }
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
