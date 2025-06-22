import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import {
    Button,
    TextControl,
    TextareaControl,
    SelectControl,
    Card,
    CardBody,
    CardHeader,
    Modal,
    Notice,
    Spinner,
    Flex,
    FlexItem,
    FlexBlock,
    __experimentalNumberControl as NumberControl,
    SearchControl,
    Pagination,
} from '@wordpress/components';
import {
    plus,
    edit,
    trash,
    book,
    search,
    filter,
    chevronLeft,
    chevronRight
} from '@wordpress/icons';

export default function App({ view = 'list', context = 'frontend' }) {
    // Estados principales
    const [books, setBooks] = useState([]);
    const [genres, setGenres] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    // Estados para paginación y filtros
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [totalBooks, setTotalBooks] = useState(0);
    const [perPage] = useState(10);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedGenre, setSelectedGenre] = useState('');
    const [selectedAuthor, setSelectedAuthor] = useState('');
    const [selectedYear, setSelectedYear] = useState('');
    const [orderBy, setOrderBy] = useState('date');
    const [order, setOrder] = useState('DESC');

    // Estados para el modal y formulario - CORREGIDO
    const [showModal, setShowModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [bookToDelete, setBookToDelete] = useState(null);
    const [editingBook, setEditingBook] = useState(null);
    const [formData, setFormData] = useState({
        title: '',
        content: '',
        author: '',
        published_year: '',
        isbn: '',
        pages: '',
        genres: []
    });

    // Estados para estadísticas
    const [stats, setStats] = useState(null);
    const [showStats, setShowStats] = useState(false);

    // Cargar datos iniciales
    useEffect(() => {
        loadGenres();
        loadBooks();
        if (context === 'admin') {
            loadStats();
        }
    }, []);

    // Recargar libros cuando cambien los filtros
    useEffect(() => {
        loadBooks();
    }, [currentPage, searchTerm, selectedGenre, selectedAuthor, selectedYear, orderBy, order]);

    // Funciones para cargar datos
    const loadBooks = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                per_page: perPage,
                page: currentPage,
                orderby: orderBy,
                order: order
            });

            if (searchTerm) params.append('search', searchTerm);
            if (selectedGenre) params.append('genre', selectedGenre);
            if (selectedAuthor) params.append('author', selectedAuthor);
            if (selectedYear) params.append('year', selectedYear);

            const response = await apiFetch({
                path: `/bookshelf/v1/books?${params}`,
                parse: false
            });

            const data = await response.json();
            const total = parseInt(response.headers.get('X-WP-Total') || '0');
            const totalPagesHeader = parseInt(response.headers.get('X-WP-TotalPages') || '1');

            setBooks(data);
            setTotalBooks(total);
            setTotalPages(totalPagesHeader);
        } catch (err) {
            setError(__('Error al cargar los libros: ') + err.message);
        }
        setLoading(false);
    };

    const loadGenres = async () => {
        try {
            const data = await apiFetch({
                path: '/bookshelf/v1/genres'
            });
            setGenres(data);
        } catch (err) {
            console.error('Error loading genres:', err);
        }
    };

    const loadStats = async () => {
        try {
            const data = await apiFetch({
                path: '/bookshelf/v1/stats'
            });
            setStats(data);
        } catch (err) {
            console.error('Error loading stats:', err);
        }
    };

    // Funciones para el formulario - CORREGIDAS
    const resetForm = () => {
        setFormData({
            title: '',
            content: '',
            author: '',
            published_year: '',
            isbn: '',
            pages: '',
            genres: []
        });
        setEditingBook(null);
    };

    const openModal = (book = null) => {
        if (book) {
            setEditingBook(book);
            setFormData({
                title: book.title || '',
                content: book.content || '',
                author: book.author || '',
                published_year: book.published_year || '',
                isbn: book.isbn || '',
                pages: book.pages || '',
                genres: book.genres ? book.genres.map(g => g.id) : []
            });
        } else {
            resetForm();
        }
        setError(''); // Limpiar errores previos
        setSuccess(''); // Limpiar mensajes de éxito previos
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setTimeout(() => {
            resetForm();
        }, 100); // Pequeño delay para evitar problemas de renderizado
    };

    // Modal de confirmación de eliminación - NUEVO
    const openDeleteModal = (book) => {
        setBookToDelete(book);
        setShowDeleteModal(true);
    };

    const closeDeleteModal = () => {
        setShowDeleteModal(false);
        setBookToDelete(null);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!formData.title.trim() || !formData.author.trim()) {
            setError(__('El título y el autor son obligatorios'));
            return;
        }

        setLoading(true);
        try {
            const method = editingBook ? 'PUT' : 'POST';
            const path = editingBook
                ? `/bookshelf/v1/books/${editingBook.id}`
                : '/bookshelf/v1/books';

            await apiFetch({
                path,
                method,
                data: formData
            });

            setSuccess(editingBook ? __('Libro actualizado correctamente') : __('Libro creado correctamente'));
            closeModal();
            loadBooks();
            if (context === 'admin') {
                loadStats();
            }
        } catch (err) {
            setError(__('Error al guardar: ') + err.message);
        }
        setLoading(false);
    };

    const handleDelete = async () => {
        if (!bookToDelete) return;

        setLoading(true);
        try {
            await apiFetch({
                path: `/bookshelf/v1/books/${bookToDelete.id}`,
                method: 'DELETE'
            });

            setSuccess(__('Libro eliminado correctamente'));
            closeDeleteModal();
            loadBooks();
            if (context === 'admin') {
                loadStats();
            }
        } catch (err) {
            setError(__('Error al eliminar: ') + err.message);
        }
        setLoading(false);
    };

    // Funciones para filtros
    const clearFilters = () => {
        setSearchTerm('');
        setSelectedGenre('');
        setSelectedAuthor('');
        setSelectedYear('');
        setCurrentPage(1);
    };

    const handleSearch = (value) => {
        setSearchTerm(value);
        setCurrentPage(1);
    };

    // Obtener autores únicos para el filtro
    const uniqueAuthors = [...new Set(books.map(book => book.author).filter(Boolean))];
    const uniqueYears = [...new Set(books.map(book => book.published_year).filter(Boolean))].sort((a, b) => b - a);

    // Renderizar componentes
    const renderFilters = () => (
        <Card className="bookshelf-filters">
            <CardHeader>
                <h3>{__('Filtros y Búsqueda')}</h3>
            </CardHeader>
            <CardBody>
                <Flex gap={3} wrap>
                    <FlexItem>
                        <SearchControl
                            value={searchTerm}
                            onChange={handleSearch}
                            placeholder={__('Buscar libros...')}
                        />
                    </FlexItem>
                    <FlexItem>
                        <SelectControl
                            label={__('Género')}
                            value={selectedGenre}
                            onChange={setSelectedGenre}
                            options={[
                                { label: __('Todos los géneros'), value: '' },
                                ...genres.map(genre => ({
                                    label: genre.name,
                                    value: genre.slug
                                }))
                            ]}
                        />
                    </FlexItem>
                    <FlexItem>
                        <SelectControl
                            label={__('Autor')}
                            value={selectedAuthor}
                            onChange={setSelectedAuthor}
                            options={[
                                { label: __('Todos los autores'), value: '' },
                                ...uniqueAuthors.map(author => ({
                                    label: author,
                                    value: author
                                }))
                            ]}
                        />
                    </FlexItem>
                    <FlexItem>
                        <SelectControl
                            label={__('Año')}
                            value={selectedYear}
                            onChange={setSelectedYear}
                            options={[
                                { label: __('Todos los años'), value: '' },
                                ...uniqueYears.map(year => ({
                                    label: year.toString(),
                                    value: year.toString()
                                }))
                            ]}
                        />
                    </FlexItem>
                    <FlexItem>
                        <SelectControl
                            label={__('Ordenar por')}
                            value={orderBy}
                            onChange={setOrderBy}
                            options={[
                                { label: __('Fecha'), value: 'date' },
                                { label: __('Título'), value: 'title' },
                                { label: __('Autor'), value: 'author' },
                                { label: __('Año'), value: 'year' }
                            ]}
                        />
                    </FlexItem>
                    <FlexItem>
                        <SelectControl
                            label={__('Orden')}
                            value={order}
                            onChange={setOrder}
                            options={[
                                { label: __('Descendente'), value: 'DESC' },
                                { label: __('Ascendente'), value: 'ASC' }
                            ]}
                        />
                    </FlexItem>
                    <FlexItem>
                        <Button
                            variant="secondary"
                            onClick={clearFilters}
                            icon={filter}
                        >
                            {__('Limpiar filtros')}
                        </Button>
                    </FlexItem>
                    <FlexItem>
                        <Button
                            variant="primary"
                            onClick={() => openModal()}
                            icon={plus}
                        >
                            {__('Nuevo Libro')}
                        </Button>
                    </FlexItem>
                </Flex>
            </CardBody>
        </Card>
    );

    const renderBookCard = (book) => (
        <Card key={book.id} className="bookshelf-book-card">
            <CardBody>
                <Flex>
                    <FlexItem>
                        {book.featured_image && (
                            <img
                                src={book.featured_image}
                                alt={book.title}
                                style={{ width: '80px', height: '120px', objectFit: 'cover' }}
                            />
                        )}
                    </FlexItem>
                    <FlexBlock>
                        <h3>{book.title}</h3>
                        <p><strong>{__('Autor:')}</strong> {book.author}</p>
                        {book.published_year && (
                            <p><strong>{__('Año:')}</strong> {book.published_year}</p>
                        )}
                        {book.pages && (
                            <p><strong>{__('Páginas:')}</strong> {book.pages}</p>
                        )}
                        {book.genres && book.genres.length > 0 && (
                            <p>
                                <strong>{__('Géneros:')}</strong>{' '}
                                {book.genres.map(genre => (
                                    <span
                                        key={genre.id}
                                        className="bookshelf-genre-tag"
                                        style={{
                                            backgroundColor: genre.color || '#ddd',
                                            color: '#fff',
                                            padding: '2px 8px',
                                            borderRadius: '12px',
                                            fontSize: '12px',
                                            marginRight: '4px'
                                        }}
                                    >
                                        {genre.name}
                                    </span>
                                ))}
                            </p>
                        )}
                        {book.content && (
                            <p className="bookshelf-excerpt">
                                {book.content.substring(0, 150)}...
                            </p>
                        )}
                    </FlexBlock>
                    {(window.bookshelfData?.canEdit || window.bookshelfData?.canDelete) && (
                        <FlexItem>
                            <Flex direction="column" gap={2}>
                                {window.bookshelfData?.canEdit && (
                                    <Button
                                        variant="secondary"
                                        size="small"
                                        icon={edit}
                                        onClick={() => openModal(book)}
                                    >
                                        {__('Editar')}
                                    </Button>
                                )}
                                {window.bookshelfData?.canDelete && (
                                    <Button
                                        variant="secondary"
                                        size="small"
                                        icon={trash}
                                        isDestructive
                                        onClick={() => openDeleteModal(book)}
                                    >
                                        {__('Eliminar')}
                                    </Button>
                                )}
                            </Flex>
                        </FlexItem>
                    )}
                </Flex>
            </CardBody>
        </Card>
    );

    const renderPagination = () => {
        if (totalPages <= 1) return null;

        return (
            <Flex justify="center" className="bookshelf-pagination">
                <Button
                    variant="secondary"
                    icon={chevronLeft}
                    disabled={currentPage === 1}
                    onClick={() => setCurrentPage(currentPage - 1)}
                >
                    {__('Anterior')}
                </Button>
                <span style={{ margin: '0 16px', alignSelf: 'center' }}>
                    {__('Página')} {currentPage} {__('de')} {totalPages}
                </span>
                <Button
                    variant="secondary"
                    icon={chevronRight}
                    disabled={currentPage === totalPages}
                    onClick={() => setCurrentPage(currentPage + 1)}
                >
                    {__('Siguiente')}
                </Button>
            </Flex>
        );
    };

    const renderStats = () => {
        if (!stats || !showStats) return null;

        return (
            <Card className="bookshelf-stats">
                <CardHeader>
                    <h3>{__('Estadísticas')}</h3>
                </CardHeader>
                <CardBody>
                    <Flex gap={4} wrap>
                        <FlexItem>
                            <div className="stat-item">
                                <h4>{stats.total_books}</h4>
                                <p>{__('Total de Libros')}</p>
                            </div>
                        </FlexItem>
                        <FlexItem>
                            <div className="stat-item">
                                <h4>{stats.total_genres}</h4>
                                <p>{__('Total de Géneros')}</p>
                            </div>
                        </FlexItem>

                        {/* Libros por año */}
                        {stats.books_by_year && Object.keys(stats.books_by_year).length > 0 && (
                            <FlexItem style={{ flexBasis: '100%', marginTop: '20px' }}>
                                <h4>{__('Libros por año')}</h4>
                                <div style={{ maxHeight: '200px', overflowY: 'auto' }}>
                                    <ul>
                                        {Object.entries(stats.books_by_year)
                                            .sort((a, b) => b[0] - a[0])
                                            .map(([year, count]) => (
                                                <li key={year} style={{ marginBottom: '8px' }}>
                                                    <strong>{year}:</strong> {count} {__('libros')}
                                                </li>
                                            ))}
                                    </ul>
                                </div>
                            </FlexItem>
                        )}

                        {/* Top autores */}
                        {stats.top_authors && Object.keys(stats.top_authors).length > 0 && (
                            <FlexItem style={{ flexBasis: '100%', marginTop: '20px' }}>
                                <h4>{__('Autores más prolíficos')}</h4>
                                <div style={{ maxHeight: '200px', overflowY: 'auto' }}>
                                    <ul>
                                        {Object.entries(stats.top_authors).map(([author, count]) => (
                                            <li key={author} style={{ marginBottom: '8px' }}>
                                                <strong>{author}:</strong> {count} {__('libros')}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </FlexItem>
                        )}
                    </Flex>
                </CardBody>
            </Card>
        );
    };

    // Modal de confirmación de eliminación - NUEVO
    const renderDeleteModal = () => (
        <Modal
            title={__('Confirmar eliminación')}
            onRequestClose={closeDeleteModal}
            isOpen={showDeleteModal}
            className="bookshelf-delete-modal"
        >
            <div style={{ padding: '20px 0' }}>
                <p>
                    {__('¿Estás seguro de que quieres eliminar el libro')} 
                    <strong> "{bookToDelete?.title}"</strong>?
                </p>
                <p style={{ color: '#d63384', fontSize: '14px' }}>
                    {__('Esta acción no se puede deshacer.')}
                </p>
            </div>
            
            <Flex justify="flex-end" gap={2}>
                <Button 
                    variant="secondary" 
                    onClick={closeDeleteModal}
                    disabled={loading}
                >
                    {__('Cancelar')}
                </Button>
                <Button 
                    variant="primary" 
                    isDestructive
                    onClick={handleDelete}
                    disabled={loading}
                >
                    {loading ? <Spinner /> : __('Eliminar')}
                </Button>
            </Flex>
        </Modal>
    );

    // Función para renderizar el formulario de libro - CORREGIDA
    const renderBookForm = () => (
        <Modal
            title={editingBook ? __('Editar Libro') : __('Añadir Nuevo Libro')}
            onRequestClose={closeModal}
            isOpen={showModal}
            className="bookshelf-modal"
            shouldCloseOnClickOutside={false} // Evita cerrar accidentalmente
        >
            <form onSubmit={handleSubmit}>
                <TextControl
                    label={__('Título *')}
                    value={formData.title}
                    onChange={(value) => setFormData({ ...formData, title: value })}
                    required
                />

                <TextareaControl
                    label={__('Descripción')}
                    value={formData.content}
                    onChange={(value) => setFormData({ ...formData, content: value })}
                    rows={4}
                />

                <TextControl
                    label={__('Autor *')}
                    value={formData.author}
                    onChange={(value) => setFormData({ ...formData, author: value })}
                    required
                />

                <Flex gap={4}>
                    <FlexItem>
                        <NumberControl
                            label={__('Año de publicación')}
                            value={formData.published_year || ''}
                            onChange={(value) => setFormData({ ...formData, published_year: value ? parseInt(value) : '' })}
                            min={1000}
                            max={new Date().getFullYear()}
                        />
                    </FlexItem>
                    <FlexItem>
                        <TextControl
                            label={__('ISBN')}
                            value={formData.isbn || ''}
                            onChange={(value) => setFormData({ ...formData, isbn: value })}
                        />
                    </FlexItem>
                    <FlexItem>
                        <NumberControl
                            label={__('Número de páginas')}
                            value={formData.pages || ''}
                            onChange={(value) => setFormData({ ...formData, pages: value ? parseInt(value) : '' })}
                            min={1}
                        />
                    </FlexItem>
                </Flex>

                {genres.length > 0 && (
                    <div className="bookshelf-genre-selector" style={{ margin: '20px 0' }}>
                        <label style={{ fontWeight: 'bold', marginBottom: '10px', display: 'block' }}>
                            {__('Géneros')}
                        </label>
                        <div className="genre-checkboxes" style={{ 
                            display: 'grid', 
                            gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', 
                            gap: '8px'
                        }}>
                            {genres.map(genre => (
                                <div key={genre.id} style={{ display: 'flex', alignItems: 'center' }}>
                                    <input
                                        type="checkbox"
                                        id={`genre-${genre.id}`}
                                        checked={formData.genres.includes(genre.id)}
                                        onChange={(e) => {
                                            const newGenres = e.target.checked
                                                ? [...formData.genres, genre.id]
                                                : formData.genres.filter(id => id !== genre.id);
                                            setFormData({ ...formData, genres: newGenres });
                                        }}
                                        style={{ marginRight: '8px' }}
                                    />
                                    <label 
                                        htmlFor={`genre-${genre.id}`} 
                                        style={{
                                            padding: '4px 8px',
                                            borderRadius: '12px',
                                            backgroundColor: genre.color || '#ddd',
                                            color: '#fff',
                                            fontSize: '12px',
                                            cursor: 'pointer'
                                        }}
                                    >
                                        {genre.name}
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <Flex justify="flex-end" gap={2} style={{ marginTop: '30px' }}>
                    <Button 
                        variant="secondary" 
                        onClick={closeModal}
                        disabled={loading}
                    >
                        {__('Cancelar')}
                    </Button>
                    <Button 
                        variant="primary" 
                        type="submit" 
                        disabled={loading}
                    >
                        {loading ? <Spinner /> : (editingBook ? __('Actualizar Libro') : __('Crear Libro'))}
                    </Button>
                </Flex>
            </form>
        </Modal>
    );

    // Render principal del componente
    return (
        <div className="bookshelf-app">
            {/* Notificaciones de error/éxito */}
            {error && (
                <Notice status="error" onRemove={() => setError('')}>
                    {error}
                </Notice>
            )}
            {success && (
                <Notice status="success" onRemove={() => setSuccess('')}>
                    {success}
                </Notice>
            )}

            {/* Barra de herramientas de administración */}
            {context === 'admin' && (
                <Card className="bookshelf-admin-toolbar">
                    <CardBody>
                        <Flex justify="space-between">
                            <FlexItem>
                                <Button
                                    variant="primary"
                                    icon={plus}
                                    onClick={() => openModal()}
                                >
                                    {__('Añadir libro')}
                                </Button>
                            </FlexItem>
                            <FlexItem>
                                <Button
                                    variant="secondary"
                                    icon={book}
                                    onClick={() => setShowStats(!showStats)}
                                >
                                    {showStats ? __('Ocultar Estadísticas') : __('Ver Estadísticas')}
                                </Button>
                            </FlexItem>
                        </Flex>
                    </CardBody>
                </Card>
            )}

            {/* Estadísticas */}
            {context === 'admin' && showStats && renderStats()}

            {/* Vista de lista de libros */}
            {view === 'list' && (
                <>
                    {/* Filtros */}
                    {renderFilters()}

                    {/* Resultados */}
                    {loading ? (
                        <div style={{ textAlign: 'center', padding: '40px' }}>
                            <Spinner />
                            <p style={{ marginTop: '10px' }}>{__('Cargando libros...')}</p>
                        </div>
                    ) : books.length === 0 ? (
                        <Card>
                            <CardBody>
                                <p style={{ textAlign: 'center', padding: '20px' }}>
                                    {__('No se encontraron libros')}
                                </p>
                            </CardBody>
                        </Card>
                    ) : (
                        <>
                            <div className="bookshelf-book-grid" style={{ marginTop: '20px' }}>
                                {books.map(renderBookCard)}
                            </div>
                            {renderPagination()}
                        </>
                    )}
                </>
            )}

            {/* Modales */}
            {showModal && renderBookForm()}
            {showDeleteModal && renderDeleteModal()}
        </div>
    );
}