<template>
  <div>
    <PageTitle title="Non-Instructor Editors"/>
    <b-modal
      id="confirm-delete-question-editor"
      ref="modal"
      title="Confirm Delete Question Editor"
    >
      <p>By deleting {{ questionEditorToRemove.name }} from the database, all of their questions
        will be moved to the Default Question Editor's account. In addition, all of the questions will be changed
        to public.</p>

      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('confirm-delete-question-editor')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="handleDeleteQuestionEditor"
        >
         Delete
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

      <div v-show="!isLoading">
        <b-card header="default" header-html="Non-Instructor Editors" class="mb-3">
          <div v-if="questionEditors.length">
            <b-table
              aria-label="Question Editors"
              striped
              hover
              :no-border-collapse="true"
              :fields="questionEditorFields"
              :items="questionEditors"
            >
              <template v-slot:cell(name)="data">
                {{ data.item.name }}
              </template>
              <template v-slot:cell(created_at)="data">
                {{ $moment(data.item.created_at, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
              </template>
              <template v-slot:cell(actions)="data">
                <div
                  v-if="data.item.is_default_non_instructor_editor"
                >
                  None
                </div>
                <div v-else>
                  <a href="" @click.prevent="initQuestionEditor(data.item)">
                    <b-icon-trash class="text-muted" :aria-label="`Remove ${data.item.name} as a Question Editor`"/>
                  </a>
                </div>
              </template>
            </b-table>
          </div>
          <div v-else>
            <b-alert show variant="info">
              <span class="font-weight-bold">There are currently no non-instructor editors.</span>
            </b-alert>
          </div>
        </b-card>
        <AccessCodes access-code-type="non-instructor editor"/>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AccessCodes from '~/components/AccessCodes'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  components: {
    Loading,
    AccessCodes
  },
  data: () => ({
    questionEditorToRemove: 0,
    isLoading: true,
    questionEditors: [],
    questionEditorFields: [
      'name',
      'email',
      {
        key: 'created_at',
        label: 'Registered On'
      },
      'actions'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    setTimeout(this.setIsLoadingToFalse, 1000)
    this.getQuestionEditors()
  },
  methods: {
    initQuestionEditor (questionEditor) {
      this.questionEditorToRemove = questionEditor
      this.$bvModal.show('confirm-delete-question-editor')
    },
    async handleDeleteQuestionEditor () {
      try {
        const { data } = await axios.delete(`/api/question-editor/${this.questionEditorToRemove.id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.questionEditors = this.questionEditors.filter(questionEditor => questionEditor.id !== this.questionEditorToRemove.id)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('confirm-delete-question-editor')
    },
    setIsLoadingToFalse () {
      this.isLoading = false
    },
    async getQuestionEditors () {
      try {
        const { data } = await axios.get('/api/question-editor')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questionEditors = data.question_editors
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
