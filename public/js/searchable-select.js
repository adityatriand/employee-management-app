/**
 * Searchable Select Component
 * Converts regular select elements into searchable dropdowns
 */
(function() {
    'use strict';

    function initSearchableSelect(selectElement) {
        // Skip if already initialized
        if (selectElement.dataset.searchable === 'true') {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select-wrapper';
        wrapper.style.position = 'relative';

        // Create the display input
        const displayInput = document.createElement('input');
        displayInput.type = 'text';
        displayInput.className = 'form-control searchable-select-display';
        if (selectElement.classList.contains('is-invalid')) {
            displayInput.classList.add('is-invalid');
        }
        displayInput.readOnly = true;
        displayInput.placeholder = selectElement.querySelector('option[value=""]')?.textContent || '-- Pilih --';
        displayInput.style.cursor = 'pointer';

        // Create the dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'searchable-select-dropdown';
        dropdown.style.display = 'none';

        // Create search input
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control searchable-select-search';
        searchInput.placeholder = 'Cari...';
        searchInput.autocomplete = 'off';

        // Create options container
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'searchable-select-options';

        // Build options from select element
        function buildOptions(filter = '') {
            optionsContainer.innerHTML = '';
            const filterLower = filter.toLowerCase();
            
            Array.from(selectElement.options).forEach((option, index) => {
                if (option.value === '' && filter !== '') {
                    return; // Skip placeholder when filtering
                }
                
                const optionText = option.textContent.toLowerCase();
                if (filter === '' || optionText.includes(filterLower) || option.value === '') {
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'searchable-select-option';
                    optionDiv.textContent = option.textContent;
                    optionDiv.dataset.value = option.value;
                    
                    if (option.selected) {
                        optionDiv.classList.add('selected');
                        displayInput.value = option.textContent;
                    }
                    
                    if (option.value === '') {
                        optionDiv.classList.add('placeholder');
                    }
                    
                    optionDiv.addEventListener('click', function(e) {
                        e.stopPropagation();
                        selectOption(option.value, option.textContent);
                    });
                    
                    optionsContainer.appendChild(optionDiv);
                }
            });
        }

        function selectOption(value, text) {
            selectElement.value = value;
            displayInput.value = value === '' ? '' : text;
            
            // Update selected state
            optionsContainer.querySelectorAll('.searchable-select-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.value === value) {
                    opt.classList.add('selected');
                }
            });

            // Trigger change event
            const event = new Event('change', { bubbles: true });
            selectElement.dispatchEvent(event);

            // Close dropdown
            closeDropdown();
        }

        function openDropdown() {
            dropdown.style.display = 'block';
            searchInput.focus();
            buildOptions();
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
            searchInput.value = '';
            buildOptions();
        }

        // Search functionality
        searchInput.addEventListener('input', function(e) {
            buildOptions(e.target.value);
        });

        // Toggle dropdown
        displayInput.addEventListener('click', function(e) {
            e.stopPropagation();
            if (dropdown.style.display === 'none') {
                openDropdown();
            } else {
                closeDropdown();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                closeDropdown();
            }
        });

        // Keyboard navigation
        let selectedIndex = -1;
        searchInput.addEventListener('keydown', function(e) {
            const options = optionsContainer.querySelectorAll('.searchable-select-option:not(.placeholder)');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, options.length - 1);
                updateHighlight(options, selectedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateHighlight(options, selectedIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && options[selectedIndex]) {
                    options[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        function updateHighlight(options, index) {
            options.forEach((opt, i) => {
                opt.classList.toggle('highlighted', i === index);
            });
            if (index >= 0 && options[index]) {
                options[index].scrollIntoView({ block: 'nearest' });
            }
        }

        // Assemble the component
        dropdown.appendChild(searchInput);
        dropdown.appendChild(optionsContainer);
        wrapper.appendChild(displayInput);
        wrapper.appendChild(dropdown);

        // Store parent and next sibling before moving select
        const parent = selectElement.parentNode;
        const nextSibling = selectElement.nextSibling;

        // Hide original select but keep it for form submission
        selectElement.style.position = 'absolute';
        selectElement.style.opacity = '0';
        selectElement.style.pointerEvents = 'none';
        selectElement.style.width = '1px';
        selectElement.style.height = '1px';
        selectElement.style.left = '-9999px';

        // Move select into wrapper first
        wrapper.appendChild(selectElement);

        // Then insert wrapper into parent at the correct position
        if (nextSibling) {
            parent.insertBefore(wrapper, nextSibling);
        } else {
            parent.appendChild(wrapper);
        }

        // Mark as initialized
        selectElement.dataset.searchable = 'true';

        // Sync validation state
        selectElement.addEventListener('invalid', function() {
            displayInput.classList.add('is-invalid');
        });

        selectElement.addEventListener('change', function() {
            if (this.value !== '') {
                displayInput.classList.remove('is-invalid');
            }
        });

        // Initial build
        buildOptions();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.searchable-select').forEach(select => {
            initSearchableSelect(select);
        });
    });

    // Export for manual initialization
    window.initSearchableSelect = initSearchableSelect;
})();

