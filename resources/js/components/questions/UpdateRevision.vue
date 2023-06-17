<template>
  <div>
    <b-modal id="modal-email-students-with-submissions"
             title="Email Students with Submissions"
             size="xl"
             no-close-on-backdrop
             @hidden="$emit('reloadSingleQuestion')"
    >
      <p>
        You can contact the affected students either by copy/pasting their email addresses into your own email
        application or ADAPT can send the email for you.
      </p>
      <p>
        Emails of students with submissions:
        <span id="student-emails">
          {{ formattedStudentEmailsAssociatedWithSubmissions }} <a href=""
                                                                   aria-label="Copy student emails"
                                                                   @click.prevent="doCopy('student-emails')"
          >
            <font-awesome-icon :icon="copyIcon" />
          </a>
        </span>
        <ErrorMessage :message="studentsWithSubmissionsForm.errors.get('emails')" />
      </p>
      <ckeditor
        id="email_to_send"
        v-model="studentsWithSubmissionsForm.message"
        tabindex="0"
        rows="6"
        :config="richEditorConfig"
        max-rows="6"
        @namespaceloaded="onCKEditorNamespaceLoaded"
        @ready="handleFixCKEditor()"
      />
      <ErrorMessage :message="studentsWithSubmissionsForm.errors.get('message')" />
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm"
                  variant="primary"
                  @click="emailStudentsWithSubmissions"
        >
          Send my students the above email
        </b-button>
        <b-button size="sm"
                  @click="$bvModal.hide('modal-email-students-with-submissions')"
        >
          I'll contact the students myself
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-show-revision"
             :key="`modal-show-revision`"
             title="Updated Version Available"
             size="xl"
    >
      <b-card header-html="<h2 class=&quot;h7&quot;>Reason For Edit</h2>">
        {{ reasonForEdit }}
      </b-card>
      <hr>
      <div class="pb-2">
        <b-button v-show="!mathJaxRendered"
                  size="sm"
                  variant="primary"
                  @click="renderMathJax()"
        >
          Render MathJax
        </b-button>
        <b-button v-show="mathJaxRendered"
                  size="sm"
                  @click="unrenderMathJax"
        >
          Unrender MathJax
        </b-button>
        <b-button v-show="!diffsShown"
                  size="sm"
                  variant="primary"
                  @click="diffsShown =true"
        >
          Show Diffs
        </b-button>
        <b-button v-show="diffsShown"
                  size="sm"
                  @click="diffsShown =false"
        >
          Hide Diffs
        </b-button>
      </div>
      <div>
        <table class="table table-striped table-responsive">
          <thead>
            <tr>
              <th>Property</th>
              <th>Current Version</th>
              <th>Revised Version</th>
            </tr>
          </thead>
          <tr v-for="(difference,differenceIndex) in differences" :key="`difference-${differenceIndex}`">
            <td>{{ difference.property }}</td>
            <td>
              <div v-html="difference.currentQuestion" />
            </td>
            <td v-show="diffsShown">
              <div v-html="difference.pendingQuestionRevision" />
            </td>
            <td v-show="!diffsShown">
              <div v-html="difference.pendingQuestionRevisionNoDiffs" />
            </td>
          </tr>
        </table>
      </div>
      <b-alert variant="danger" show>
        <b-form-checkbox
          id="checkbox-1"
          v-model="understandStudentSubmissionsRemoved"
          name="student_submissions_removed"
          :value="true"
          :unchecked-value="false"
        >
          I understand that student submissions for this question will be removed. Please inform your class to resubmit.
        </b-form-checkbox>
      </b-alert>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm"
                  variant="primary"
                  @click="updateTheQuestionRevision"
        >
          Update
        </b-button>
        <b-button size="sm"
                  @click="$bvModal.hide('modal-show-revision')"
        >
          Cancel
        </b-button>
      </template>
    </b-modal>

    <b-alert show variant="secondary" class="text-center">
      <h5>
        The current question has an <a href="" @click.prevent="showRevision">updated version</a> available.
      </h5>
    </b-alert>
  </div>
</template>

<script>
import axios from 'axios'
import { labelMapping } from '~/helpers/Revisions'
import Diff from 'vue-jsdiff'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Form from 'vform'
import { mapGetters } from 'vuex'
import ErrorMessage from '../ErrorMessage.vue'

export default {
  name: 'UpdateRevision',
  components: {
    ErrorMessage,
    FontAwesomeIcon,
    ckeditor: CKEditor.component
  },
  props: {
    questionNumber: {
      type: Number,
      default: 0
    },
    assignmentName: {
      type: String,
      default: ''
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    currentQuestion: {
      type: Object,
      default: () => {
      }
    },
    pendingQuestionRevision: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    diffsShown: true,
    studentsWithSubmissionsForm: new Form({
      message: '',
      emails: []
    }),
    richEditorConfig: {
      toolbar: [],
      removeButtons: '',
      height: 200
    },
    copyIcon: faCopy,
    formattedStudentEmailsAssociatedWithSubmissions: '',
    mathJaxRendered: false,
    differences: [],
    reasonForEdit: '',
    understandStudentSubmissionsRemoved: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.showDifferences()
    console.log('differences')
    console.log(this.differences)
  },
  methods: {
    async emailStudentsWithSubmissions () {
      try {
        this.studentsWithSubmissionsForm.assignment_id = this.assignmentId
        const { data } = await this.studentsWithSubmissionsForm.post(`/api/question-revisions/email-students-with-submissions`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.$bvModal.hide('modal-email-students-with-submissions')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    doCopy,
    showDifferences () {
      let revised
      for (const property in this.pendingQuestionRevision) {
        if (property === 'reason_for_edit') {
          this.reasonForEdit = this.pendingQuestionRevision.reason_for_edit
        }
        revised = false
        if (typeof this.pendingQuestionRevision[property] === 'string') {
          console.log(this.pendingQuestionRevision[property])
          let currentQuestionProperty = typeof this.currentQuestion[property] === 'string' ? this.currentQuestion[property].replaceAll('\n', '') : this.currentQuestion[property]
          revised = this.pendingQuestionRevision[property].replaceAll('\n', '') !== currentQuestionProperty
        } else {
          revised = this.pendingQuestionRevision[property] !== this.currentQuestion[property] && ((this.pendingQuestionRevision[property] || this.currentQuestion[property]))
        }

        if (revised && !['created_at', 'updated_at', 'revision_number', 'reason_for_edit', 'technology_iframe', 'action', 'id'].includes(property)) {
          let text = ''
          try {
            const diff = Diff.diffChars(this.currentQuestion[property], this.pendingQuestionRevision[property])
            diff.forEach((part) => {
              const color = part.added ? 'green' : part.removed ? 'red' : 'grey'
              text += '<span style="color:' + color + '">' + part.value + '</span>'
            })
          } catch (error) {
            text = 'N/A'
          }
          this.differences.push({
            property: labelMapping[property] ? labelMapping[property] : property,
            currentQuestion: this.currentQuestion[property],
            pendingQuestionRevision: text,
            pendingQuestionRevisionNoDiffs: this.pendingQuestionRevision[property]
          })
        }
      }
    },
    unrenderMathJax () {
      this.mathJaxRendered = false
      this.differences = []
      this.$nextTick(() => {
        this.showDifferences()
        this.$forceUpdate()
      })
    },
    async updateTheQuestionRevision () {
      if (!this.understandStudentSubmissionsRemoved) {
        this.$noty.info('Please check the box stating that you understand that all existing student submissions will be removed and their assignment scores will be updated.')
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/question/${this.currentQuestion.id}/update-to-latest-revision`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        console.log(data)
        this.$bvModal.hide('modal-show-revision')
        this.initEmailStudentsWithSubmissions(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initEmailStudentsWithSubmissions (data) {
      if (data.student_emails_associated_with_submissions.length) {
        this.studentsWithSubmissionsForm.emails = data.student_emails_associated_with_submissions
        this.formattedStudentEmailsAssociatedWithSubmissions = data.student_emails_associated_with_submissions.join(', ')
        this.studentsWithSubmissionsForm.studentEmails = data.student_emails_associated_with_submissions
        let lastName = this.user.last_name
        this.studentsWithSubmissionsForm.message = `<p>Hi,</p><p>There was an issue with Question #${this.questionNumber} in Assignment ${this.assignmentName}.&nbsp; Because of this, you'll need to resubmit your response to this question.</p><p>-Professor ${lastName}</p>`
        this.$bvModal.show('modal-email-students-with-submissions')
      } else {
        this.$emit('reloadSingleQuestion')
      }
    },
    renderMathJax () {
      this.mathJaxRendered = true
      this.$nextTick(() => {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub])
      })
    },
    showRevision () {
      this.understandStudentSubmissionsRemoved = 0
      this.$bvModal.show('modal-show-revision')
    }

  }
}
</script>
