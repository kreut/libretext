<template>
  <div>
    <ViewQuestions :key="`view-selected-questions-clicked-${numViewSelectedQuestionsClicked}`"
                   :question-ids-to-view="selectedQuestionIds"
                   :modal-id="'questions-to-view-my-questions'"
    />
    <b-modal
      id="modal-edit-question"
      :title="`Edit Question &quot;${questionToEdit.title}&quot;`"
      :no-close-on-esc="true"
      size="xl"
      hide-footer
    >
      <CreateQuestion :key="`question-to-edit-${questionToEdit.id}`"
                      :question-to-edit="questionToEdit"
                      :parent-get-my-questions="getMyQuestions"
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
          <span v-html="data.item.deleted_status" />
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
              {{ data.item.title }}
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
              <a :id="getTooltipTarget('view',data.item.id)"
                 href=""
                 class="pr-1"
                 @click.prevent="selectedQuestionIds=[data.item.id];viewSelectedQuestions()"
              >
                <b-icon class="text-muted"
                        icon="eye"
                        :aria-label="`View ${data.item.title}`"
                />
              </a>
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
        <span class="font-weight-bold font-italic">
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

export default {
  name: 'MyQuestions',
  components: {
    Loading,
    CreateQuestion,
    ViewQuestions
  },
  data: () => ({
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
        key: 'page_id',
        sortable: true,
        label: 'Page ID'
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
      'actions'
    ]
  }
  ),
  mounted () {
    this.getMyQuestions()
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  },
  methods: {
    async editQuestion (questionToEdit) {
      this.questionToEdit = questionToEdit
      if (parseInt(this.questionToEdit.non_technology) === 0) {
        this.questionToEdit.question_type = 'auto_graded'
      } else {
        this.questionToEdit.question_type =
          this.questionToEdit.technology === 'text'
            ? 'open_ended'
            : 'frankenstein'
        await this.getNonTechnologyText()
      }
      this.$bvModal.show('modal-edit-question')
    },
    async getNonTechnologyText () {
      try {
        const { data } = await axios.get(`/api/get-locally-saved-page-contents/adapt/${this.questionToEdit.page_id}`)
        this.questionToEdit.non_technology_text = data
        console.log(this.questionForm)
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