<template>
  <div>
    <PageTitle title="Discipline Manager"/>
    <b-modal id="modal-delete-discipline"
             title="Delete Discipline"
             no-close-on-backdrop
    >
      <p>Please confirm that you would like to delete the discipline:</p>
      <p class="text-center font-weight-bold">
        {{ activeDiscipline.text }}
      </p>
      Once this discipline is deleted, it will be removed from all associated courses.

      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-delete-discipline')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteDiscipline(activeDiscipline.value)"
        >
          Delete
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-edit-new-discipline"
             :title="activeDiscipline.value ? 'Edit Discipline' : 'New Discipline'"
             no-close-on-backdrop
             @hidden="resetDiscipline"
    >
      <b-form-input id="description"
                    v-model="disciplineForm.name"
                    :class="{ 'is-invalid': disciplineForm.errors.has('name') }"
                    style="width:400px"
                    type="text"
                    @keydown="disciplineForm.errors.clear('name')"
      />
      <has-error :form="disciplineForm" field="name"/>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="disciplineForm.errors.clear();$bvModal.hide('modal-edit-new-discipline')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="saveDiscipline(activeDiscipline.value)"
        >
          Save
        </b-button>
      </template>
    </b-modal>
    <div class="mb-3">
      <b-button size="sm"
                variant="outline-primary"
                class="mt-2"
                @click="$bvModal.show('modal-edit-new-discipline')"
      >
        Add
      </b-button>
    </div>
    <b-table
      :items="disciplineOptions"
      :fields="disciplineFields"
      small
      bordered
      responsive
    >
      <template #cell(actions)="row">
        <b-button size="sm"
                  variant="outline-info"
                  @click="initEditDiscipline(row.item)"
        >
          <b-icon-pencil></b-icon-pencil>
        </b-button>
        <b-button size="sm"
                  variant="outline-danger"
                  class="ml-1"
                  @click="initDeleteDiscipline(row.item)"
        >
          <b-icon-trash></b-icon-trash>
        </b-button>
      </template>
    </b-table>
  </div>
</template>

<script>
import {
  getDisciplines,
  initEditDiscipline,
  initDeleteDiscipline,
  saveDiscipline,
  resetDiscipline,
  deleteDiscipline
} from '~/helpers/Disciplines'
import Form from 'vform'

export default {
  name: 'disciplineManager',
  data: () => ({
    disciplineForm: new Form({ name: '' }),
    disciplineOptions: [],
    activeDiscipline: {},
    disciplineFields: [
      { key: 'text', label: 'Discipline' },
      { key: 'actions', label: 'Actions', thStyle: { width: '160px' }, tdClass: 'text-center' }
    ]
  }),
  mounted () {
    this.getDisciplines(true)
  },
  methods: {
    getDisciplines,
    initEditDiscipline,
    initDeleteDiscipline,
    saveDiscipline,
    resetDiscipline,
    deleteDiscipline
  }

}
</script>
