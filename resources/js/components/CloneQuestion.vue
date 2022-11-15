<template>
  <span>
    <b-modal :id="`modal-clone-question-${questionId}`"
             :title="`Clone ${title}`"
             size="lg"
             @shown="getNonBetaCoursesAndAssignments()"
    >
      <div v-if="isMe">
        <span>Acting as</span>
        <toggle-button
          style="margin-bottom:5px"
          tabindex="0"
          :width="100"
          :value="actingAs === user.first_name"
          :sync="true"
          :font-size="14"
          :color="toggleColors"
          :labels="{checked: user.first_name, unchecked: 'Admin'}"
          @change="updateActingAs()"
        />
      </div>
      <div v-if="actingAs === user.first_name">
        <RequiredText/>
        <div class="inline-flex d-flex pb-2">
          Clone question to*
          <SavedQuestionsFolders
            type="my_questions"
            class="pl-2"
            :question-source-is-my-favorites="false"
            :modal-id="`modal-for-bulk-import-${questionId}`"
            :default-is-copied-questions-folder="true"
            :folder-to-choose-from="'My Questions'"
            :create-modal-add-saved-questions-folder="true"
            @savedQuestionsFolderSet="setCloneToFolderId"
          />
        </div>
        <div class="inline-flex d-flex pb-2">
          <span style="padding-right: 18px">Add To (optional)</span>
          <span class="pr-2">
            <b-form-select
              id="course"
              v-model="courseId"
              style="width:300px"
              size="sm"
              :options="courseOptions"
              @change="cloneForm.assignment_id=null;updateAssignments($event)"
            />
          </span>
          <b-form-select id="assignment"
                         v-model="cloneForm.assignment_id"
                         style="width:300px"
                         size="sm"
                         :disabled="courseId === null"
                         :options="assignmentOptions"
          />

        </div>
      </div>
      <div v-if="actingAs === 'Admin'">
        <p>The copied question will be moved to the new owner's account.</p>
        <v-select id="owner"
                  v-model="questionEditor"
                  placeholder="Please choose the new owner"
                  :options="questionEditorOptions"
                  style="width:300px"
        />
      </div>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide(`modal-clone-question-${questionId}`)">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary"
                  @click="cloneQuestion()"
        >
          Clone
        </b-button>
      </template>

    </b-modal>
    <a :id="`clone-${questionId}`"
       href=""
       style="text-decoration: none"
       @click.prevent="openModalCopyQuestion()"
    ><span class="align-middle">
         <b-button v-if="asButton" size="sm" variant="outline-secondary">
           <font-awesome-icon
             :id="`clone-${questionId}`"
             :class="canClone ? 'text-muted' : 'text-danger'"
             :icon="copyIcon"
           />
        Clone</b-button>
      <span v-if="!asButton">
       <font-awesome-icon
         v-if="bigIcon"
         :id="`clone-${questionId}`"
         :class="canClone ? 'text-muted' : 'text-danger'"
         :icon="copyIcon"
         style="font-size:24px;"
       />
     </span>
      <font-awesome-icon
        v-if="!bigIcon"
        :class="canClone ? 'text-muted' : 'text-danger'"
        :icon="copyIcon"
      /> </span></a>
    <b-tooltip :target="`clone-${questionId}`"
               triggers="hover"
               delay="750"
    > <span v-if="canClone">
        <span v-if="isMe">Make a clone of question {{
            questionId
          }} to your account or that of another instructor's.</span>
        <span v-if="!isMe">Clone {{ title }}.</span>
      </span>
      <span v-if="!canClone">
        <span v-if="['ccbync', 'arr'].includes(license)">
         Due to licensing restrictions, this question cannot be cloned.
        </span>
        <span v-if="!this.public">
          Since this question is not public, it cannot be cloned.
        </span>
      </span>
    </b-tooltip>
  </span>
</template>

<script>
import axios from 'axios'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import SavedQuestionsFolders from './SavedQuestionsFolders'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'
import Form from 'vform'

export default {
  name: 'CloneQuestion',
  components: {
    FontAwesomeIcon,
    SavedQuestionsFolders,
    ToggleButton
  },
  props: {
    asButton: {
      type: Boolean,
      default: false
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    bigIcon: {
      type: Boolean,
      default: false
    },
    questionId: {
      type: Number,
      default: 0
    },
    questionEditorUserId: {
      type: Number,
      default: 0
    },
    title: {
      type: String,
      default: ''
    },
    library: {
      type: String,
      default: ''
    },
    license: {
      type: String,
      default: ''
    },
    public: {
      type: Number,
      default: 1
    },
    nonTechnology: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    canClone: true,
    courseId: null,
    courseOptions: [{ value: null, text: 'Please choose a course' }],
    assignmentOptions: [{ value: null, text: 'Please choose an assignment' }],
    cloneForm: new Form({
      assignment_id: null,
      clone_to_folder_id: null
    }),
    actingAs: '',
    toggleColors: window.config.toggleColors,
    copiedQuestionsFolder: true,
    addToAssignment: true,
    questionEditorOptions: [],
    questionEditor: null,
    copyIcon: faCopy
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.actingAs = this.user.first_name
    this.canClone = true
    if (this.user.id !== this.questionEditorUserId) {
      this.canClone = !['ccbync', 'arr'].includes(this.license) && this.public
    }
  },
  methods: {
    updateAssignments (courseId) {
      this.assignmentOptions = [{ value: null, text: 'Please choose an assignment' }]
      let assignments = this.assignments.find(course => course.course_id === courseId).assignments
      console.log(assignments)
      for (let i = 0; i < assignments.length; i++) {
        let assignment = assignments[i]
        let assignmentOption = { value: assignment.assignment_id, text: assignment.assignment_name }
        this.assignmentOptions.push(assignmentOption)
      }
    },
    async getNonBetaCoursesAndAssignments () {
      try {
        const { data } = await axios.get('/api/courses/non-beta-courses-and-assignments')
        if (this.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < data.courses.length; i++) {
          let course = data.courses[i]
          let courseOption = { value: course.course_id, text: course.course_name }
          this.courseOptions.push(courseOption)
        }
        this.courses = data.courses
        this.assignments = data.assignments
        console.log(this.courses)
        console.log(this.assignments)
        if (this.assignmentId) {
          for (let i = 0; i < this.assignments.length; i++) {
            let courseAssignments = this.assignments[i]
            let assignmentInfo = courseAssignments.assignments.find(assignment => assignment.assignment_id === this.assignmentId)
            if (assignmentInfo) {
              this.courseId = courseAssignments.course_id
              this.updateAssignments(this.courseId)
              this.cloneForm.assignment_id = this.assignmentId
              return
            }
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    setCloneToFolderId (folderId) {
      this.cloneForm.clone_to_folder_id = folderId
      console.log(folderId)
    },
    updateActingAs () {
      this.actingAs = this.actingAs === this.user.first_name ? 'Admin' : this.user.first_name
    },
    openModalCopyQuestion () {
      if (!this.canClone) {
        return false
      }
      this.getAllQuestionEditors()
      this.$bvModal.show(`modal-clone-question-${this.questionId}`)
    },
    async cloneQuestion () {
      this.cloneForm.acting_as = this.actingAs === 'Admin' ? 'admin' : 'instructor'
      this.cloneForm.question_id = this.questionId
      this.cloneForm.question_editor_user_id = this.actingAs === 'Admin' ? this.questionEditor.value : this.user.id
      if (this.cloneForm.acting_as === 'admin') {
        this.cloneForm.assignment_id = 0
        this.cloneForm.clone_to_folder_id = 0;
      }
      if (!this.cloneForm.assignment_id && this.courseId) {
        this.$noty.error('Please either choose an assignment or do not choose a course.')
        return false
      }
      try {
        const { data } = await this.cloneForm.post('/api/questions/clone')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$emit('reloadQuestions')
          this.$bvModal.hide(`modal-clone-question-${this.questionId}`)
          this.cloneForm.clone_to_folder_id = null
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAllQuestionEditors () {
      try {
        const { data } = await axios.get('/api/user/question-editors')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questionEditorOptions = data.question_editors
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
