<style>
  table.vgt-table img.avatar-search {
    border-radius: 3px;
    display: inline-block;
    height: 43px;
    width: 43px;
  }

  /* Make checkbox column more clickable */
  table.vgt-table .vgt-checkbox-col {
    cursor: pointer;
    user-select: none;
  }

  table.vgt-table .vgt-checkbox-col:hover {
    background-color: rgba(0, 0, 0, 0.02);
  }
</style>

<style lang="scss" scoped>
  a {
    color: inherit;
    text-decoration: none;
    vertical-align: middle;
    background: transparent;
  }

  a:hover {
    background: inherit;
    color: inherit;
  }

  .bulk-actions-toolbar {
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal-content {
    background: white;
    border-radius: 8px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
  }

  .modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
  }

  .close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }

  .badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 0.25rem;
    background-color: #007bff;
    color: white;
  }

  .badge button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0;
  }
</style>

<template>
  <div>
    <!-- Selection Helper Bar -->
    <div class="selection-helper mb2 pa2 bg-near-white ba b--light-gray br2">
      <div class="flex items-center justify-between flex-wrap">
        <div class="flex items-center">
          <button class="btn btn-sm btn-secondary mr2" @click="selectAllOnPage">
            Select All on Page
          </button>
          <button class="btn btn-sm btn-secondary mr2" @click="deselectAll">
            Deselect All
          </button>
          <span class="f6 gray">Tip: Hold Shift and click to select range</span>
        </div>
        <div v-if="selectedContacts.length > 0" class="f6">
          <strong>{{ selectedContacts.length }}</strong> of {{ contacts.length }} selected
        </div>
      </div>
    </div>

    <!-- Bulk Actions Toolbar -->
    <div v-if="selectedContacts.length > 0" class="bulk-actions-toolbar mb3 pa3 bg-light-gray ba b--moon-gray br2">
      <div class="flex items-center justify-between flex-wrap">
        <div class="flex items-center mb2 mb0-ns">
          <strong class="mr3">{{ selectedContacts.length }} selected</strong>
          <button class="btn btn-secondary mr2" @click="clearSelection">
            Clear Selection
          </button>
        </div>
        <div class="flex items-center mb2 mb0-ns">
          <button class="btn btn-primary mr2" @click="showBulkEditModal = true">
            Bulk Edit
          </button>
          <button class="btn btn-danger" @click="confirmBulkDelete">
            Delete Selected
          </button>
        </div>
      </div>
    </div>

    <vue-good-table
      ref="contactTable"
      mode="remote"
      :columns="columns"
      :rows="contacts"
      :total-rows="totalRecords"
      :row-style-class="getRowStyleClass"
      style-class="vgt-table"
      :rtl="!dirltr"
      :select-options="{
        enabled: true,
        selectOnCheckboxOnly: true,
      }"
      :sort-options="{
        enabled: false,
      }"
      :pagination-options="{
        enabled: true,
        position: 'bottom',
        perPage: serverParams.perPage,
        page: 1,
        perPageDropdown: perPageDropdown,
        mode: 'pages',
        nextLabel: $t('people.people_search_next'),
        prevLabel: $t('people.people_search_prev'),
        rowsPerPageLabel: $t('people.people_search_rows_per_page'),
        ofLabel: $t('people.people_search_of'),
        pageLabel: $t('people.people_search_page'),
        allLabel: $t('people.people_search_all'),
      }"
      :search-options="{
        enabled: true,
        placeholder: $t('people.people_search'),
      }"
      @on-page-change="onPageChange"
      @on-per-page-change="onPerPageChange"
      @on-search="onSearch"
      @on-row-click="onRowClick"
      @on-selected-rows-change="onSelectionChanged"
      @on-cell-click="onCellClick"
    >
      <div slot="emptystate" class="tc">
        {{ $t('people.people_search_no_results') }}
      </div>
      <template #emptystate>
        <div v-if="!ready" class="vgt-center-align vgt-text-disabled h3">
          {{ $t('app.loading') }}
        </div>
        <div v-else class="vgt-center-align vgt-text-disabled h3">
          {{ $t('people.people_search_no_results') }}
        </div>
      </template>
      <template slot="table-row" slot-scope="props">
        <template v-if="props.column.field === 'avatar'">
          <contact-item
            :item="props.row"
            :with-name="false"
            :class-name="'avatar-search'"
          />
        </template>
        <template v-else-if="props.column.field === 'name'">
          <a :href="props.row.route">
            <template v-if="props.row.is_starred">
              <span :class="[dirltr === 'ltr' ? 'ml3' : 'mr3']">
                {{ props.row.complete_name }}
              </span>
              <svg class="relative" style="top: 5px" width="23" height="22" viewBox="0 0 23 22"
                   fill="none"
                   xmlns="http://www.w3.org/2000/svg"
              >
                <path d="M14.6053 7.404L14.7224 7.68675L15.0275 7.7111C16.7206 7.84628 18.1486 7.92359 19.2895 7.98536C19.9026 8.01855 20.4327 8.04725 20.8765 8.07803C21.5288 8.12327 21.9886 8.17235 22.2913 8.24003C22.3371 8.25027 22.3765 8.26035 22.4102 8.27001C22.3896 8.29619 22.3651 8.32572 22.336 8.35877C22.1328 8.58945 21.7914 8.89714 21.2913 9.31475C20.9474 9.60184 20.5299 9.93955 20.0459 10.331C19.1625 11.0455 18.0577 11.9391 16.7762 13.0302L16.543 13.2288L16.614 13.5267C17.0045 15.1663 17.3689 16.5406 17.6601 17.6391C17.819 18.2381 17.956 18.7552 18.0638 19.1886C18.2209 19.8206 18.3149 20.2704 18.3428 20.5768C18.347 20.6222 18.3493 20.6616 18.3505 20.6957C18.3176 20.6838 18.2798 20.6688 18.2367 20.6502C17.9532 20.5277 17.5539 20.2981 17.0014 19.9522C16.6264 19.7175 16.1833 19.4306 15.671 19.099C14.7143 18.4797 13.5162 17.7042 12.0695 16.8199L11.8087 16.6604L11.5478 16.82C10.0753 17.7209 8.86032 18.5085 7.89223 19.136C7.3851 19.4648 6.94572 19.7496 6.57253 19.9838C6.01576 20.3332 5.61353 20.5656 5.32808 20.6899C5.28721 20.7077 5.25111 20.7222 5.21941 20.7339C5.22088 20.7009 5.22355 20.663 5.22783 20.6197C5.25839 20.3111 5.35605 19.8582 5.51781 19.2225C5.62627 18.7962 5.76269 18.2914 5.92018 17.7087C6.22053 16.5972 6.59748 15.2024 7.00309 13.5286L7.07553 13.2297L6.84141 13.0303C5.52399 11.9079 4.39683 10.9982 3.50024 10.2747C3.03915 9.90254 2.63904 9.57963 2.30539 9.30232C1.80195 8.88388 1.45729 8.57562 1.25116 8.34437C1.22315 8.31293 1.19929 8.28466 1.17903 8.25939C1.20999 8.25084 1.24557 8.24198 1.28628 8.233C1.58841 8.1663 2.048 8.11835 2.701 8.07418C3.1353 8.0448 3.65101 8.01744 4.24568 7.98589C5.39523 7.9249 6.83989 7.84824 8.56208 7.71111L8.86638 7.68688L8.98388 7.40514C9.61646 5.88824 10.1238 4.58366 10.5314 3.53571C10.7656 2.93365 10.9668 2.4163 11.1399 1.99205C11.3854 1.39027 11.5751 0.972355 11.7339 0.708729C11.7601 0.66516 11.7838 0.628777 11.8048 0.598565C11.8256 0.628571 11.849 0.664658 11.8748 0.707817C12.0327 0.971308 12.2212 1.38911 12.465 1.99089C12.6368 2.41509 12.8365 2.93242 13.0689 3.53445C13.4735 4.58244 13.9771 5.88709 14.6053 7.404Z"
                      fill="#F2C94C" stroke="#DCBB58"
                />
              </svg>
            </template>
            <span v-else>
              {{ props.row.complete_name }}
            </span>
          </a>
        </template>
        <template v-else-if="props.column.field === 'description'">
          <a :href="props.row.route">
            <span v-if="props.row.description" :class="['i', dirltr === 'ltr' ? 'ml3' : 'mr3']">
              {{ props.row.description }}
            </span>
          </a>
        </template>
      </template>
    </vue-good-table>

    <!-- Bulk Edit Modal -->
    <div v-if="showBulkEditModal" class="modal-overlay" @click="closeBulkEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Bulk Edit Contacts</h3>
          <button class="close-button" @click="closeBulkEditModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="mb3">
            <label class="db mb2 fw6">Gender</label>
            <select v-model="bulkEditData.gender_id" class="form-control">
              <option :value="null">-- No Change --</option>
              <option v-for="gender in genders" :key="gender.id" :value="gender.id">
                {{ gender.name }}
              </option>
            </select>
          </div>
          <div class="mb3">
            <label class="db mb2 fw6">Add Tags</label>
            <input
              v-model="tagInput"
              type="text"
              class="form-control"
              placeholder="Type tag name and press Enter"
              @keydown.enter.prevent="addTag"
            >
            <div v-if="bulkEditData.tags.length > 0" class="mt2">
              <span
                v-for="(tag, index) in bulkEditData.tags"
                :key="index"
                class="badge mr2 mb2"
              >
                {{ tag }}
                <button class="ml1" @click="removeTag(index)">&times;</button>
              </span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary mr2" @click="closeBulkEditModal">
            Cancel
          </button>
          <button class="btn btn-primary" :disabled="isProcessing" @click="applyBulkEdit">
            {{ isProcessing ? 'Processing...' : 'Apply Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ContactItem from './partials/ContactItem.vue';

export default {

  components: {
    ContactItem,
  },

  props: {
    showArchived: {
      type: Boolean,
      default: false,
    },
    debounceWait: {
      type: Number,
      default: 200,
    },
  },

  data() {
    return {
      contacts: [],
      searchEntries: null,
      ready: false,
      selectedContacts: [],
      lastSelectedIndex: null, // For shift-select functionality
      showBulkEditModal: false,
      isProcessing: false,
      genders: [],
      bulkEditData: {
        gender_id: null,
        tags: [],
      },
      tagInput: '',

      totalRecords: 0,
      perPageDropdown: [30, 50, 100],

      serverParams: {
        search: '',
        page: 1,
        perPage: 30
      },

      columns: [
        {
          label: this.$t('app.contact_list_avatar'),
          field: 'avatar',
          width: '70px',
          sortable: false,
        },
        {
          label: this.$t('app.contact_list_name'),
          field: 'name',
        },
        {
          label: this.$t('app.contact_list_description'),
          field: 'description',
        }
      ],
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  mounted() {
    this.searchEntries = _.debounce(() => {
      this.ready = false;
      this.contacts = [];
      this._loadItems();
    }, this.debounceWait);

    this._loadItems();
    this._loadGenders();
  },

  methods: {
    onRowClick(params) {
      // Don't navigate when clicking checkbox or checkbox cell
      if (params.event.target.type === 'checkbox') {
        return;
      }

      // Check if click is in the checkbox column (first column with vgt-checkbox-col class)
      const clickedElement = params.event.target;
      const checkboxCell = clickedElement.closest('.vgt-checkbox-col');

      if (checkboxCell) {
        // Click is in checkbox column - toggle selection instead of navigating
        params.event.preventDefault();
        params.event.stopPropagation();
        this.toggleRowSelection(params.row, params.pageIndex, params.event.shiftKey);
        return;
      }

      params.event.preventDefault();
      if (params.event.ctrlKey) {
        window.open(params.row.route, '_blank');
        return;
      }
      window.location.href = params.row.route;
    },

    toggleRowSelection(row, rowIndex, shiftKey) {
      const checkbox = document.querySelector(`[data-row-index="${rowIndex}"] input[type="checkbox"]`);

      if (checkbox) {
        // Simulate checkbox click
        checkbox.click();

        // Handle shift-select
        if (shiftKey) {
          this.handleShiftSelect(row, rowIndex);
        } else {
          this.lastSelectedIndex = rowIndex;
        }
      }
    },

    onSelectionChanged(params) {
      this.selectedContacts = params.selectedRows;
    },

    onCellClick(params) {
      const checkboxCell = params.event.target.closest('.vgt-checkbox-col');
      if (!checkboxCell) return;

      params.event.stopPropagation();

      if (params.event.shiftKey) {
        // vue-good-table already toggled the clicked checkbox; fill in the rest of the range
        this.handleShiftSelect(params.row, params.rowIndex);
      } else if (params.event.target.type === 'checkbox') {
        this.lastSelectedIndex = params.rowIndex;
      }
    },

    handleShiftSelect(clickedRow, clickedIndex) {
      if (this.lastSelectedIndex === null) {
        this.lastSelectedIndex = clickedIndex;
        return;
      }

      const start = Math.min(this.lastSelectedIndex, clickedIndex);
      const end = Math.max(this.lastSelectedIndex, clickedIndex);

      // Click unchecked checkboxes in the range via the table's own DOM rows
      const tableRows = this.$refs.contactTable.$el.querySelectorAll('tbody tr');
      for (let i = start; i <= end; i++) {
        if (tableRows[i]) {
          const checkbox = tableRows[i].querySelector('input[type="checkbox"]');
          if (checkbox && !checkbox.checked) {
            checkbox.click();
          }
        }
      }

      this.lastSelectedIndex = clickedIndex;
    },

    selectAllOnPage() {
      // Select all contacts on current page
      if (this.$refs.contactTable && this.$refs.contactTable.selectAllRows) {
        this.$refs.contactTable.selectAllRows();
      } else {
        // Fallback: manually select all
        this.selectedContacts = [...this.contacts];
      }
    },

    deselectAll() {
      this.clearSelection();
    },

    clearSelection() {
      this.selectedContacts = [];
      this.lastSelectedIndex = null;
      // Clear checkboxes in vue-good-table
      if (this.$refs.contactTable && this.$refs.contactTable.unselectAllRows) {
        this.$refs.contactTable.unselectAllRows();
      }
    },

    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    onPageChange(params) {
      this.updateParams({page: params.currentPage});
      this.searchEntries();
    },

    onPerPageChange(params) {
      this.updateParams({perPage: params.currentPerPage, page: 1});
      this.searchEntries();
    },

    onSearch(params) {
      this.updateParams({search: params.searchTerm});
      this.searchEntries();
    },

    // load items is what brings back the rows from server
    _loadItems() {
      let urlParam = window.location.search;

      if (urlParam) {
        urlParam += '&';
      } else {
        urlParam += '?';
      }

      urlParam += 'page='+this.serverParams.page;
      urlParam += '&perPage='+this.serverParams.perPage;
      urlParam += '&search='+this.serverParams.search;
      if (this.showArchived) {
        urlParam += '&show_archived=true';
      }

      this._loadNewItems(urlParam, (entries, total) => {
        this.contacts = entries;
        this.totalRecords = total;
        this.ready = true;
      });
    },

    _loadNewItems(urlParam, after) {
      axios.get('people/list'+urlParam)
        .then(response => {
          if (_.isFunction(after)) {
            after(
              _.uniqBy(response.data.contacts, entry => _.uniqueId()),
              response.data.totalRecords
            );
          }
        });
    },

    _loadGenders() {
      axios.get('/api/genders')
        .then(response => {
          this.genders = response.data.data;
        })
        .catch(error => {
          console.error('Failed to load genders:', error);
        });
    },

    getRowStyleClass() {
      return 'people-list-item bg-white pointer';
    },

    closeBulkEditModal() {
      this.showBulkEditModal = false;
      this.bulkEditData = {
        gender_id: null,
        tags: [],
      };
      this.tagInput = '';
    },

    addTag() {
      if (this.tagInput.trim() && !this.bulkEditData.tags.includes(this.tagInput.trim())) {
        this.bulkEditData.tags.push(this.tagInput.trim());
        this.tagInput = '';
      }
    },

    removeTag(index) {
      this.bulkEditData.tags.splice(index, 1);
    },

    async applyBulkEdit() {
      if (this.selectedContacts.length === 0) {
        return;
      }

      this.isProcessing = true;
      const contactIds = this.selectedContacts.map(c => c.id);
      const promises = [];

      try {
        // Update gender if changed
        if (this.bulkEditData.gender_id !== null) {
          promises.push(
            axios.post('/api/contacts/bulk/gender', {
              contact_ids: contactIds,
              gender_id: this.bulkEditData.gender_id,
            })
          );
        }

        // Add tags if any
        if (this.bulkEditData.tags.length > 0) {
          promises.push(
            axios.post('/api/contacts/bulk/tags', {
              contact_ids: contactIds,
              tags: this.bulkEditData.tags,
            })
          );
        }

        await Promise.all(promises);

        // Reload contacts
        this._loadItems();
        this.clearSelection();
        this.closeBulkEditModal();

        // Show success message
        alert('Bulk edit completed successfully!');
      } catch (error) {
        console.error('Bulk edit failed:', error);
        alert('Bulk edit failed. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },

    confirmBulkDelete() {
      if (this.selectedContacts.length === 0) {
        return;
      }

      const count = this.selectedContacts.length;
      if (!confirm(`Are you sure you want to delete ${count} contact(s)? This action cannot be undone.`)) {
        return;
      }

      this.performBulkDelete();
    },

    async performBulkDelete() {
      this.isProcessing = true;
      const contactIds = this.selectedContacts.map(c => c.id);

      try {
        await axios.post('/api/contacts/bulk/destroy', {
          contact_ids: contactIds,
        });

        // Reload contacts
        this._loadItems();
        this.clearSelection();

        alert('Contacts deleted successfully!');
      } catch (error) {
        console.error('Bulk delete failed:', error);
        alert('Bulk delete failed. Please try again.');
      } finally {
        this.isProcessing = false;
      }
    },
  }
};
</script>
