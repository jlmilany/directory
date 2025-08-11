const searchInput = document.getElementById('search');
  const tbody = document.querySelector('#resultsTable tbody');
  const loadingSpinner = document.getElementById('loadingSpinner');

  let debounceTimeout;

  function showLoading(show) {
    loadingSpinner.style.display = show ? 'block' : 'none';
  }

  async function fetchResults(query) {
    showLoading(true);
    try {
      const response = await fetch('search.php?search=' + encodeURIComponent(query));
      if (!response.ok) {
        console.error('Network response was not ok');
        return [];
      }
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Fetch error:', error);
      return [];
    } finally {
      showLoading(false);
    }
  }

  function renderTable(rows) {
    tbody.innerHTML = '';

    if (rows.length === 0) {
      tbody.innerHTML = `
        <tr role="row" class="no-results" aria-live="polite">
          <td colspan="3" role="gridcell">
            <div class="no-results">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
              <h3>No Results Found</h3>
              <p>Try adjusting your search terms or adding new contacts.</p>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    rows.forEach((row, i) => {
      const tr = document.createElement('tr');
      tr.setAttribute('role', 'row');
      tr.setAttribute('aria-rowindex', i + 2); // +2 for header + 1-based indexing

      ['building_name', 'department', 'number'].forEach(key => {
        const td = document.createElement('td');
        td.setAttribute('role', 'gridcell');
        td.textContent = row[key];
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });

    // Update aria-rowcount
    const table = document.getElementById('resultsTable');
    table.setAttribute('aria-rowcount', rows.length + 1); // +1 header row
  }

  function debounce(func, delay) {
    return (...args) => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => func(...args), delay);
    };
  }

  const handleSearch = debounce(async () => {
    const query = searchInput.value.trim();
    const results = await fetchResults(query);
    renderTable(results);
  }, 300);

  searchInput.addEventListener('input', handleSearch);

  // Initial load
  (async () => {
    const results = await fetchResults('');
    renderTable(results);
  })();
