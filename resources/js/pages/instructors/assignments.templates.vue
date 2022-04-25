<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-assignment-template'"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <PageTitle v-if="!isLoading" title="Assignment Templates"/>
      <b-container>
        <b-row>
          <p>
            Assignment templates can be used to create assignments with the same basic structure.
            When bulk-uploading questions into courses, specify the name of the assignment and the associated template and
            ADAPT will populate the assignment information on the fly.
          </p>
        </b-row>
      </b-container>
      <b-modal
        id="modal-confirm-delete-assignment-template"
        ref="modal"
        title="Delete Assignment Template"
      >
        <p>
          You are about to delete the assignment template:
        </p>
        <p class="text-center font-weight-bold">
          {{ assignmentTemplateToDelete.template_name }}
        </p>
        <p>
          No assignments that currently use this template will be affected.
        </p>
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-confirm-delete-assignment-template')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary"
                    @click="handleDeleteAssignmentTemplate()"
          >
            Submit
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-assignment-template"
        ref="modal"
        title="Assignment Template"
        size="lg"
        no-close-on-backdrop
        @hidden="resetAssignmentForm(form,0)"
        @shown="updateModalToggleIndex('modal-assignment-template')"
      >
        <AssignmentProperties
          :assignment-groups="assignmentGroups"
          :form="form"
          :course-id="0"
          :course-start-date="null"
          :course-end-date="null"
          :assignment-id="0"
          :is-beta-assignment="false"
          :lms="false"
          :has-submissions-or-file-submissions="false"
          :is-alpha-course="false"
          :overall-status-is-not-open="false"
        />
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-assignment-template')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary"
                    @click="handleSubmitAssignmentTemplateInfo()"
          >
            Submit
          </b-button>
        </template>
      </b-modal>
      <b-row align-h="end" class="mb-4">
        <b-button variant="primary" class="mr-1"
                  size="sm" @click="assignmentTemplateId=0;$bvModal.show('modal-assignment-template')"
        >
          New Template
        </b-button>
      </b-row>
      <div v-if="!isLoading">
        <div v-if="assignmentTemplates.length">
          <table class="table table-striped" aria-label="Assignment List">
            <thead>
            <tr>
              <th scope="col">
                Name
              </th>
              <th scope="col">
                Description
              </th>
              <th scope="col">
                Actions
              </th>
            </tr>
            </thead>
            <tbody is="draggable" v-model="assignmentTemplates" :key="assignmentTemplates.length" tag="tbody"
                   @end="saveNewOrder"
            >
            <tr
              v-for="assignmentTemplate in assignmentTemplates"
              :key="`assignment-template-${assignmentTemplate.id}`"
            >
              <th scope="row" style="width:300px">
                <b-icon icon="list"/>
                <span :id="`assignment-template-${assignmentTemplate.id}`">
                  {{ assignmentTemplate.template_name }}
                </span>
                <font-awesome-icon
                  :icon="copyIcon"
                  aria-label="Copy Template Name"
                  @click.prevent="doCopy(`assignment-template-${assignmentTemplate.id}`)"
                />
              </th>
              <td>{{ assignmentTemplate.template_description }}</td>
              <td>
                <b-icon class="text-muted"
                        icon="pencil"
                        :aria-label="`Edit ${assignmentTemplate.template_name}`"
                        @click="initEditAssignmentProperties(assignmentTemplate)"
                />
                <font-awesome-icon
                  class="text-muted"
                  :icon="copyIcon"
                  :aria-label="`Copy ${assignmentTemplate.template_description}`"
                  @click="copyAssignmentTemplate(assignmentTemplate)"
                />
                <b-icon class=" text-muted"
                        icon="trash"
                        :aria-label="`Delete ${assignmentTemplate.template_description}`"
                        @click="confirmDeleteAssignmentTemplate(assignmentTemplate)"
                />
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        <div v-else>
          <b-alert show variant="info">
            You currently have no assignment templates.
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import AssignmentProperties from '~/components/AssignmentProperties'
import { updateModalToggleIndex } from '~/helpers/accessibility/fixCKEditor'
import AllFormErrors from '~/components/AllFormErrors'
import draggable from 'vuedraggable'
import {
  getAssignmentGroups,
  resetAssignmentForm,
  assignmentForm,
  initAddAssignment,
  editAssignmentProperties
} from '~/helpers/AssignmentProperties'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { doCopy } from '~/helpers/Copy'
import axios from 'axios'

export default {
  components: {
    Loading,
    AssignmentProperties,
    AllFormErrors,
    FontAwesomeIcon,
    draggable
  },
  metaInfo () {
    return {
      title: 'Assignment Templates'
    }
  },
  data: () => ({
    assignmentTemplateToDelete: {},
    copyIcon: faCopy,
    assignmentTemplateId: 0,
    allFormErrors: [],
    isLoading: true,
    assignmentTemplates: [],
    assignmentGroups: [],
    form: assignmentForm
  }),
  middleware: 'auth',
  created () {
    this.initAddAssignment = initAddAssignment
    this.resetAssignmentForm = resetAssignmentForm
    this.updateModalToggleIndex = updateModalToggleIndex
    this.editAssignmentProperties = editAssignmentProperties
  },
  async mounted () {
    await this.getAssignmentTemplates()
    this.doCopy = doCopy
    this.getAssignmentGroups = getAssignmentGroups
    this.assignmentGroups = await getAssignmentGroups(this.courseId, this.$noty)
    this.isLoading = false
    this.form.is_template = true
    this.initAddAssignment(this.form, 0, this.assignmentGroups, this.$noty, null, null, null, null)
  },
  methods: {
    async saveNewOrder () {
      let orderedAssignmentTemplates = []
      for (let i = 0; i < this.assignmentTemplates.length; i++) {
        orderedAssignmentTemplates.push(this.assignmentTemplates[i].id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedAssignmentTemplates.length; i++) {
        if (this.currentOrderedAssignmentTemplates[i] !== this.assignmentTemplates[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignment-templates/order`, { ordered_assignment_templates: orderedAssignmentTemplates })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.assignmentTemplates.length; i++) {
            this.assignmentTemplates[i].order = i + 1
          }
          this.currentOrderedAssignmentTemplates = this.assignmentTemplates
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initEditAssignmentProperties (assignmentTemplate) {
      assignmentTemplate.is_template = true
      editAssignmentProperties(assignmentTemplate, this)
    },
    confirmDeleteAssignmentTemplate (assignmentTemplate) {
      this.assignmentTemplateToDelete = assignmentTemplate
      this.$bvModal.show('modal-confirm-delete-assignment-template')
    },
    async handleDeleteAssignmentTemplate () {
      try {
        const { data } = await axios.delete(`/api/assignment-templates/${this.assignmentTemplateToDelete.id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-confirm-delete-assignment-template')
          await this.getAssignmentTemplates()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
    ,
    async copyAssignmentTemplate (assignmentTemplate) {
      try {
        const { data } = await axios.patch(`/api/assignment-templates/copy/${assignmentTemplate.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getAssignmentTemplates()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleSubmitAssignmentTemplateInfo () {
      try {
        const { data } = this.assignmentTemplateId
          ? await this.form.patch(`/api/assignment-templates/${this.assignmentTemplateId}`)
          : await this.form.post('/api/assignment-templates')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getAssignmentTemplates()
          this.$bvModal.hide('modal-assignment-template')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          fixInvalid()
          this.allFormErrors = this.form.errors.flatten()
          console.log(this.allFormErrors)
          this.$bvModal.show('modal-form-errors-assignment-template')
        }
      }
    },
    async getAssignmentTemplates () {
      try {
        const { data } = await axios.get('/api/assignment-templates')
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assignmentTemplates = data.assignment_templates
        this.currentOrderedAssignmentTemplates = this.assignmentTemplates
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
