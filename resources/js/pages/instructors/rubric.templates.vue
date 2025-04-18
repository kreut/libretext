<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-rubric-templates'" />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <PageTitle v-if="!isLoading" title="Rubric Templates" />
      <b-container>
        <b-row>
          <p>
            Rubric templates can be used to create re-usable rubrics for assignment questions. They can be edited at the
            assignment question level.
          </p>
        </b-row>
      </b-container>
      <b-modal
        id="modal-confirm-delete-rubric-template"
        title="Delete Rubric"
      >
        <p>
          You are about to delete the rubric template:
        </p>
        <p class="text-center font-weight-bold">
          {{ activeRubricTemplate.name }}
        </p>
        <p>
          No questions that currently use this template will be affected.
        </p>
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-confirm-delete-rubric-template')">
            Cancel
          </b-button>
          <b-button size="sm"
                    variant="danger"
                    @click="handleDeleteRubricTemplate()"
          >
            Delete
          </b-button>
        </template>
      </b-modal>
      <RubricPropertiesModal :key="`show-rubric-properties-${+showRubricProperties}`"
                        :show-rubric-properties="showRubricProperties"
                        :is-edit="isEditRubricTemplate"
                        :rubric-info="activeRubricTemplate"
                        :is-template="true"
                        @hideRubricProperties="showRubricProperties = false"
                        @reloadRubricTemplates="getRubricTemplates"
      />
      <b-row align-h="end" class="mb-4">
        <b-button variant="primary" class="mr-1"
                  size="sm"
                  @click="initNewRubricTemplate()"
        >
          New Rubric Template
        </b-button>
      </b-row>
      <div v-if="!isLoading">
        <div v-if="rubricTemplates.length">
          <table class="table table-striped" aria-label="Rubric List">
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
            <tbody>
              <tr
                v-for="rubricTemplate in rubricTemplates"
                :key="`rubric-${rubricTemplate.id}`"
              >
                <th scope="row" style="width:300px">
                  <span :id="`rubric-${rubricTemplate.id}`">
                    {{ rubricTemplate.name }}
                  </span>
                </th>
                <td>
                  {{ rubricTemplate.description }}
                </td>
                <td>
                  <b-tooltip :target="`edit-rubric-template-${rubricTemplate.id}`" triggers="hover focus" delay="500">
                    Edit {{ rubricTemplate.name }}
                  </b-tooltip>
                  <b-icon :id="`edit-rubric-template-${rubricTemplate.id}`"
                          class="text-muted"
                          icon="pencil"
                          style="cursor:pointer;"
                          :aria-label="`Edit ${rubricTemplate.name}`"
                          @click="initEditRubricTemplateProperties(rubricTemplate)"
                  />
                  <b-tooltip :target="`copy-rubric-template-${rubricTemplate.id}`" triggers="hover focus" delay="500">
                    Copy {{ rubricTemplate.name }}
                  </b-tooltip>
                  <font-awesome-icon
                    :id="`copy-rubric-template-${rubricTemplate.id}`"
                    class="text-muted"
                    style="cursor:pointer;"
                    :icon="copyIcon"
                    :aria-label="`Copy ${rubricTemplate.description}`"
                    @click="copyRubricTemplate(rubricTemplate)"
                  />
                  <b-tooltip :target="`delete-rubric-template-${rubricTemplate.id}`" triggers="hover focus" delay="500">
                    Delete {{ rubricTemplate.name }}
                  </b-tooltip>
                  <b-icon :id="`delete-rubric-template-${rubricTemplate.id}`"
                          class="text-muted"
                          icon="trash"
                          style="cursor:pointer;"
                          :aria-label="`Delete ${rubricTemplate.description}`"
                          @click="confirmDeleteRubricTemplate(rubricTemplate)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else>
          <b-alert show variant="info">
            You currently have no rubric templates.
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AllFormErrors from '~/components/AllFormErrors.vue'
import { doCopy } from '~/helpers/Copy'
import axios from 'axios'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import RubricPropertiesModal from '../../components/RubricPropertiesModal.vue'

export default {
  components: {
    RubricPropertiesModal,
    FontAwesomeIcon,
    AllFormErrors,
    Loading
  },
  data: () => ({
    activeRubricTemplate: {},
    isEditRubricTemplate: false,
    showRubricProperties: false,
    copyIcon: faCopy,
    isLoading: true,
    allFormErrors: [],
    rubricTemplates: [],
    rubricTemplate: {},
    rubricId: 0,
    currentOrderedRubricTemplates: []
  }
  ),
  mounted () {
    this.getRubricTemplates()
  },
  methods: {
    doCopy,
    async copyRubricTemplate (rubricTemplate) {
      try {
        const { data } = await axios.patch(`/api/rubric-templates/${rubricTemplate.id}/copy`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getRubricTemplates()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initNewRubricTemplate () {
      this.isEditRubricTemplate = false
      this.activeRubricTemplate = {}
      this.showRubricProperties = true
    },
    async getRubricTemplates () {
      try {
        const { data } = await axios.get('/api/rubric-templates')
        if (data.type === 'success') {
          this.rubricTemplates = data.rubric_templates
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async saveNewOrder () {
      let orderedRubricTemplates = []
      for (let i = 0; i < this.rubricTemplates.length; i++) {
        orderedRubricTemplates.push(this.rubricTemplates[i].id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedRubricTemplates.length; i++) {
        if (this.currentOrderedRubricTemplates[i] !== this.rubricTemplates[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/rubric-templates/order`, { ordered_rubric_templates: orderedRubricTemplates })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.rubricTemplates.length; i++) {
            this.rubricTemplates[i].order = i + 1
          }
          this.currentOrderedRubricTemplates = this.rubricTemplates
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initEditRubricTemplateProperties (rubricTemplate) {
      this.activeRubricTemplate = rubricTemplate
      this.isEditRubricTemplate = true
      this.showRubricProperties = true
    },
    async handleDeleteRubricTemplate () {
      try {
        const { data } = await axios.delete(`/api/rubric-templates/${this.activeRubricTemplate.id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'info') {
          this.$bvModal.hide('modal-confirm-delete-rubric-template')
          await this.getRubricTemplates()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    confirmDeleteRubricTemplate (rubricTemplate) {
      this.activeRubricTemplate = rubricTemplate
      this.$bvModal.show('modal-confirm-delete-rubric-template')
    }
  }
}
</script>

