# Flutter Integration Guide - Firebird Anggota Import

**API Base URL:** `http://127.0.0.1:8000/api`

## Quick Info

- **Search minimal 3 karakter** (returns 422 jika <3)
- **Max 100 results** per request (Firebird only)
- **All endpoints butuh auth** (Bearer token)

## 📊 Cara Kerja Pagination (Backend ↔ Frontend)

### **Backend (Laravel):**
- **Menyediakan** data per halaman (default 15 atau 20 sesuai request)
- **Frontend yang kontrol** mau ambil berapa data via parameter `per_page`
- Backend **hanya kirim 1 page** sesuai request

### **Frontend (Flutter):**
- **Request ke backend** dengan parameter:
  - `page=1` → halaman pertama
  - `per_page=20` → minta 20 data per halaman
- **Backend response** dengan:
  - `data`: array 20 items
  - `meta`: info total data, current page, last page, dll
- **Frontend bisa pilih**:
  - **Opsi A**: Tampilkan 20 data → user klik "Load More" → request page=2
  - **Opsi B**: Auto-load semua page (loop request page 1,2,3... sampai habis)

### **Contoh Flow:**

**Request Frontend:**
```
GET /api/anggota?page=1&per_page=20
```

**Response Backend:**
```json
{
  "data": [/* 20 items */],
  "meta": {
    "current_page": 1,
    "total": 156,        // Total ada 156 data
    "per_page": 20,      // Per page 20 data
    "last_page": 8       // Total 8 halaman (156 / 20 = 8)
  }
}
```

**Frontend tahu:**
- Dapat 20 data
- Total ada 156 data
- Masih ada 7 halaman lagi (page 2-8)
- Bisa request page 2 untuk dapat 20 data berikutnya

### **Rekomendasi Setting:**

| Use Case | Backend per_page | Frontend Strategy |
|----------|------------------|-------------------|
| **Tabel dengan navigasi page (Next/Prev)** | 10-20 | **Server Pagination (Pattern D)** ⭐ |
| **Tabel besar (1000+ data)** | 20-50 | Infinite Scroll (Pattern B) |
| **Master data kecil (<500)** | 50-100 | Auto-load semua (Pattern C) |
| **Dropdown/Autocomplete** | 50 | Search-based (Pattern A - Firebird) |

---

## 1. Setup Dependencies

```yaml
dependencies:
  dio: ^5.3.2
  shared_preferences: ^2.2.2
```

---

## 2. API Client

```dart
// lib/services/api_client.dart
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiClient {
  static const String baseUrl = 'http://127.0.0.1:8000/api';
  late Dio _dio;

  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
    ));
  }

  Dio get dio => _dio;
}
```

---

## 3. Models

```dart
// lib/models/firebird_anggota.dart
class FirebirdAnggota {
  final String noAgt;
  final String? nama;
  final String? idKs;

  FirebirdAnggota({
    required this.noAgt,
    this.nama,
    this.idKs,
  });

  factory FirebirdAnggota.fromJson(Map<String, dynamic> json) {
    return FirebirdAnggota(
      noAgt: json['NO_AGT'],
      nama: json['NAMA'],
      idKs: json['ID_KS'],
    );
  }

  String get displayText => '$noAgt - $nama';
}

class FirebirdListResponse {
  final List<FirebirdAnggota> data;
  final int total;
  final int showing;
  final bool hasMore;

  FirebirdListResponse({
    required this.data,
    required this.total,
    required this.showing,
    required this.hasMore,
  });

  factory FirebirdListResponse.fromJson(Map<String, dynamic> json) {
    return FirebirdListResponse(
      data: (json['data'] as List)
          .map((item) => FirebirdAnggota.fromJson(item))
          .toList(),
      total: json['meta']['total'],
      showing: json['meta']['showing'],
      hasMore: json['meta']['has_more'],
    );
  }
}

// lib/models/paginated_response.dart
// Untuk endpoint MySQL yang menggunakan pagination standar Laravel
class PaginatedResponse<T> {
  final List<T> data;
  final PaginationMeta meta;
  final PaginationLinks links;

  PaginatedResponse({
    required this.data,
    required this.meta,
    required this.links,
  });

  factory PaginatedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    return PaginatedResponse(
      data: (json['data'] as List).map((item) => fromJson(item)).toList(),
      meta: PaginationMeta.fromJson(json['meta']),
      links: PaginationLinks.fromJson(json['links']),
    );
  }
}

class PaginationMeta {
  final int currentPage;
  final int from;
  final int lastPage;
  final int perPage;
  final int to;
  final int total;

  PaginationMeta({
    required this.currentPage,
    required this.from,
    required this.lastPage,
    required this.perPage,
    required this.to,
    required this.total,
  });

  factory PaginationMeta.fromJson(Map<String, dynamic> json) {
    return PaginationMeta(
      currentPage: json['current_page'],
      from: json['from'] ?? 0,
      lastPage: json['last_page'],
      perPage: json['per_page'],
      to: json['to'] ?? 0,
      total: json['total'],
    );
  }

  bool get hasMorePages => currentPage < lastPage;
}

class PaginationLinks {
  final String? first;
  final String? last;
  final String? prev;
  final String? next;

  PaginationLinks({
    this.first,
    this.last,
    this.prev,
    this.next,
  });

  factory PaginationLinks.fromJson(Map<String, dynamic> json) {
    return PaginationLinks(
      first: json['first'],
      last: json['last'],
      prev: json['prev'],
      next: json['next'],
    );
  }
}
```

---

## 4. Service

```dart
// lib/services/firebird_service.dart
import 'package:dio/dio.dart';
import '../models/firebird_anggota.dart';
import 'api_client.dart';

class FirebirdService {
  final Dio _dio = ApiClient().dio;

  // Search anggota (min 3 chars)
  Future<FirebirdListResponse> searchAnggota(String query) async {
    if (query.length < 3) {
      throw Exception('Search minimal 3 karakter');
    }

    try {
      final response = await _dio.get(
        '/import-anggota-firebird',
        queryParameters: {'search': query, 'per_page': 50},
      );
      return FirebirdListResponse.fromJson(response.data);
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Network error');
    }
  }

  // Preview single anggota
  Future<Map<String, dynamic>> previewAnggota(String noAgt) async {
    try {
      final response = await _dio.get('/import-anggota-firebird/$noAgt');
      return response.data['data'];
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Network error');
    }
  }

  // Import anggota to MySQL
  Future<void> importAnggota(String noAgt) async {
    try {
      await _dio.post('/import-anggota-firebird', data: {
        'no_agt': noAgt,
        'confirm_import': true,
      });
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Network error');
    }
  }
}
```

---

## 5. Autocomplete Widget

```dart
// lib/widgets/anggota_autocomplete.dart
import 'package:flutter/material.dart';
import '../models/firebird_anggota.dart';
import '../services/firebird_service.dart';

class AnggotaAutocomplete extends StatefulWidget {
  final Function(FirebirdAnggota) onSelected;
  final String? labelText;

  const AnggotaAutocomplete({
    Key? key,
    required this.onSelected,
    this.labelText = 'Pilih Anggota',
  }) : super(key: key);

  @override
  State<AnggotaAutocomplete> createState() => _AnggotaAutocompleteState();
}

class _AnggotaAutocompleteState extends State<AnggotaAutocomplete> {
  final FirebirdService _service = FirebirdService();
  List<FirebirdAnggota> _options = [];
  bool _isLoading = false;

  Future<void> _search(String query) async {
    if (query.length < 3) {
      setState(() => _options = []);
      return;
    }

    setState(() => _isLoading = true);

    try {
      final response = await _service.searchAnggota(query);
      setState(() {
        _options = response.data;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _options = [];
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Autocomplete<FirebirdAnggota>(
      optionsBuilder: (textEditingValue) async {
        await _search(textEditingValue.text);
        return _options;
      },
      displayStringForOption: (option) => option.displayText,
      onSelected: widget.onSelected,
      fieldViewBuilder: (context, controller, focusNode, onSubmit) {
        return TextField(
          controller: controller,
          focusNode: focusNode,
          decoration: InputDecoration(
            labelText: widget.labelText,
            hintText: 'Ketik min 3 karakter...',
            border: const OutlineInputBorder(),
            suffixIcon: _isLoading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: Padding(
                      padding: EdgeInsets.all(12.0),
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  )
                : const Icon(Icons.search),
            helperText: controller.text.isNotEmpty && controller.text.length < 3
                ? 'Minimal 3 karakter'
                : null,
          ),
        );
      },
      optionsViewBuilder: (context, onSelected, options) {
        return Align(
          alignment: Alignment.topLeft,
          child: Material(
            elevation: 4.0,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 200),
              child: ListView.builder(
                padding: EdgeInsets.zero,
                shrinkWrap: true,
                itemCount: options.length,
                itemBuilder: (context, index) {
                  final option = options.elementAt(index);
                  return ListTile(
                    title: Text(option.nama ?? 'N/A'),
                    subtitle: Text('NO_AGT: ${option.noAgt}'),
                    onTap: () => onSelected(option),
                  );
                },
              ),
            ),
          ),
        );
      },
    );
  }
}
```

---

## 6. Usage Example

### A. Firebird Autocomplete (Import Form)

**Use Case:** Form input anggota dengan autocomplete dari Firebird

```dart
import 'package:flutter/material.dart';
import 'widgets/anggota_autocomplete.dart';
import 'models/firebird_anggota.dart';
import 'services/firebird_service.dart';

class AnggotaImportForm extends StatefulWidget {
  @override
  State<AnggotaImportForm> createState() => _AnggotaImportFormState();
}

class _AnggotaImportFormState extends State<AnggotaImportForm> {
  final FirebirdService _firebirdService = FirebirdService();
  final _formKey = GlobalKey<FormState>();
  
  // Data dari Firebird yang dipilih
  Map<String, dynamic>? _firebirdData;
  bool _isLoadingPreview = false;
  bool _isSaving = false;

  // Form controllers
  final _noAgtController = TextEditingController();
  final _namaController = TextEditingController();
  final _idKsController = TextEditingController();

  Future<void> _onAnggotaSelected(FirebirdAnggota anggota) async {
    setState(() => _isLoadingPreview = true);

    try {
      // Preview data lengkap dari Firebird
      final data = await _firebirdService.previewAnggota(anggota.noAgt);
      
      setState(() {
        _firebirdData = data['firebird_data'];
        
        // Isi form dengan data dari Firebird
        _noAgtController.text = _firebirdData!['NO_AGT'] ?? '';
        _namaController.text = _firebirdData!['NAMA'] ?? '';
        _idKsController.text = _firebirdData!['ID_KS'] ?? '';
        
        _isLoadingPreview = false;
      });
    } catch (e) {
      setState(() => _isLoadingPreview = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  Future<void> _saveData() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);

    try {
      // Import dari Firebird ke MySQL
      await _firebirdService.importAnggota(_noAgtController.text);
      
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Data berhasil disimpan!')),
      );
      
      Navigator.pop(context);
    } catch (e) {
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Import Anggota dari Firebird')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            children: [
              // Autocomplete untuk cari anggota
              AnggotaAutocomplete(
                onSelected: _onAnggotaSelected,
                labelText: 'Cari Anggota (min 3 karakter)',
              ),
              
              const SizedBox(height: 20),
              
              if (_isLoadingPreview)
                const CircularProgressIndicator()
              else if (_firebirdData != null) ...[
                // Form fields auto-filled dari Firebird
                TextFormField(
                  controller: _noAgtController,
                  decoration: const InputDecoration(labelText: 'NO_AGT'),
                  enabled: false,
                ),
                const SizedBox(height: 12),
                
                TextFormField(
                  controller: _namaController,
                  decoration: const InputDecoration(labelText: 'NAMA'),
                  validator: (v) => v?.isEmpty ?? true ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                
                TextFormField(
                  controller: _idKsController,
                  decoration: const InputDecoration(labelText: 'ID_KS'),
                ),
                
                const SizedBox(height: 24),
                
                ElevatedButton(
                  onPressed: _isSaving ? null : _saveData,
                  child: _isSaving
                      ? const CircularProgressIndicator()
                      : const Text('SIMPAN'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### B. Tabel dengan Infinite Scroll (Recommended)

**Use Case:** Tabel besar, load data per 20 otomatis saat scroll

```dart
// lib/services/anggota_service.dart
class AnggotaService {
  final Dio _dio = ApiClient().dio;

  Future<PaginatedResponse<Anggota>> getAnggotaList({
    int page = 1,
    int perPage = 20,  // Backend kirim 20 data per request
    String? noAgt,
    String? idKs,
  }) async {
    try {
      final response = await _dio.get('/anggota', queryParameters: {
        'page': page,
        'per_page': perPage,
        if (noAgt != null) 'NO_AGT': noAgt,
        if (idKs != null) 'ID_KS': idKs,
      });

      return PaginatedResponse.fromJson(
        response.data,
        (json) => Anggota.fromJson(json),
      );
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Network error');
    }
  }
}

// lib/screens/anggota_list_screen.dart
class AnggotaListScreen extends StatefulWidget {
  @override
  State<AnggotaListScreen> createState() => _AnggotaListScreenState();
}

class _AnggotaListScreenState extends State<AnggotaListScreen> {
  final AnggotaService _service = AnggotaService();
  final ScrollController _scrollController = ScrollController();
  
  List<Anggota> _anggotaList = [];
  bool _isLoading = false;
  int _currentPage = 1;
  bool _hasMorePages = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _loadData();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  // Detect scroll ke bawah
  void _onScroll() {
    if (_scrollController.position.pixels ==
        _scrollController.position.maxScrollExtent) {
      if (_hasMorePages && !_isLoading) {
        _loadNextPage();
      }
    }
  }

  // Load page pertama (20 data)
  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    try {
      final response = await _service.getAnggotaList(
        page: 1,
        perPage: 20,  // Minta 20 data dari backend
      );

      setState(() {
        _anggotaList = response.data;  // Dapat 20 data
        _currentPage = 1;
        _hasMorePages = response.meta.hasMorePages;  // Cek masih ada page lagi?
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  // Load page berikutnya (otomatis saat scroll)
  Future<void> _loadNextPage() async {
    setState(() => _isLoading = true);

    try {
      final response = await _service.getAnggotaList(
        page: _currentPage + 1,  // Request page 2, 3, 4...
        perPage: 20,
      );

      setState(() {
        _anggotaList.addAll(response.data);  // Tambah 20 data lagi
        _currentPage++;
        _hasMorePages = response.meta.hasMorePages;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Anggota (${_anggotaList.length} dari banyak)'),
      ),
      body: _anggotaList.isEmpty && _isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              controller: _scrollController,
              itemCount: _anggotaList.length + (_hasMorePages ? 1 : 0),
              itemBuilder: (context, index) {
                // Loading indicator di akhir list
                if (index == _anggotaList.length) {
                  return const Padding(
                    padding: EdgeInsets.all(16.0),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }

                final anggota = _anggotaList[index];
                return ListTile(
                  title: Text(anggota.nama ?? 'N/A'),
                  subtitle: Text('NO_AGT: ${anggota.noAgt}'),
                  trailing: Text('ID_KS: ${anggota.idKs ?? "-"}'),
                );
              },
            ),
    );
  }
}
```

**Cara Kerja:**
1. **Initial load**: Request `page=1&per_page=20` → dapat 20 data
2. **User scroll ke bawah**: Otomatis request `page=2&per_page=20` → dapat 20 data lagi
3. **Total di list**: 40 data (20 + 20)
4. **Terus loop** sampai `hasMorePages = false`

---

### C. Load All Data Sekaligus (Untuk Master Kecil)

**Use Case:** Dropdown atau tabel master kecil (<500 data)

```dart
class DataLoService {
  final Dio _dio = ApiClient().dio;

  // Load semua data sekaligus dengan loop
  Future<List<DataLo>> getAllDataLo() async {
    List<DataLo> allData = [];
    int currentPage = 1;
    bool hasMore = true;

    while (hasMore) {
      try {
        final response = await _dio.get('/data-lo', queryParameters: {
          'page': currentPage,
          'per_page': 50,  // Backend kirim 50 per request
        });

        final paginated = PaginatedResponse<DataLo>.fromJson(
          response.data,
          (json) => DataLo.fromJson(json),
        );

        allData.addAll(paginated.data);  // Kumpulkan semua
        
        hasMore = paginated.meta.hasMorePages;
        currentPage++;
        
        // Safety: max 20 page (50 x 20 = 1000 data)
        if (currentPage > 20) break;
        
      } catch (e) {
        throw Exception('Failed to load data: $e');
      }
    }

    return allData;
  }
}

// Usage
class DataLoDropdown extends StatefulWidget {
  @override
  State<DataLoDropdown> createState() => _DataLoDropdownState();
}

class _DataLoDropdownState extends State<DataLoDropdown> {
  final DataLoService _service = DataLoService();
  List<DataLo> _allLo = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadAllData();
  }

  Future<void> _loadAllData() async {
    try {
      final data = await _service.getAllDataLo();
      setState(() {
        _allLo = data;  // Dapat semua data (misal 156 data)
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const CircularProgressIndicator();
    }

    return DropdownButton<String>(
      items: _allLo.map((lo) {
        return DropdownMenuItem(
          value: lo.idLo,
          child: Text(lo.nama ?? ''),
        );
      }).toList(),
      onChanged: (value) {},
    );
  }
}
```

**Cara Kerja:**
1. Loop request page 1, 2, 3... dengan `per_page=50`
2. Backend kirim 50 data per request
3. Frontend kumpulkan semua sampai `hasMorePages = false`
4. Total request: Misal 156 data / 50 = 4 request (4 x 50 = 200, ambil 156)

---

### D. Server Pagination dengan Next/Previous (⭐ Recommended)

**Use Case:** Tabel dengan tombol navigasi page (Previous, Page X of Y, Next)

**Screenshot Model:**
```
[ < Previous ]  Page 1 of 8  [ Next > ]
Showing 1-10 of 156 records
```

**Keunggulan:**
- ✅ **Memory efficient**: Hanya simpan 10-20 data (current page saja)
- ✅ **Fast navigation**: User bisa lompat page dengan cepat
- ✅ **Best for tables**: Cocok untuk UI dengan Previous/Next buttons
- ✅ **Scalable**: Aman untuk 1000+ data (tidak load sekaligus)

```dart
// lib/services/anggota_service.dart (REUSE service yang sama)
class AnggotaService {
  final Dio _dio = ApiClient().dio;

  Future<PaginatedResponse<Anggota>> getAnggotaList({
    int page = 1,
    int perPage = 10,  // 10 data per page untuk UI dengan Next/Prev
    String? noAgt,
    String? idKs,
  }) async {
    try {
      final response = await _dio.get('/anggota', queryParameters: {
        'page': page,
        'per_page': perPage,
        if (noAgt != null) 'NO_AGT': noAgt,
        if (idKs != null) 'ID_KS': idKs,
      });

      return PaginatedResponse.fromJson(
        response.data,
        (json) => Anggota.fromJson(json),
      );
    } on DioException catch (e) {
      throw Exception(e.response?.data['message'] ?? 'Network error');
    }
  }
}

// lib/screens/anggota_table_screen.dart
class AnggotaTableScreen extends StatefulWidget {
  @override
  State<AnggotaTableScreen> createState() => _AnggotaTableScreenState();
}

class _AnggotaTableScreenState extends State<AnggotaTableScreen> {
  final AnggotaService _service = AnggotaService();
  
  // State untuk current page saja (TIDAK akumulasi)
  List<Anggota> _currentPageData = [];
  bool _isLoading = false;
  
  // Pagination state
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;
  int _perPage = 10;
  int _from = 0;
  int _to = 0;

  @override
  void initState() {
    super.initState();
    _loadPage(_currentPage);
  }

  // Load specific page (REPLACE data, bukan tambah)
  Future<void> _loadPage(int page) async {
    setState(() => _isLoading = true);

    try {
      final response = await _service.getAnggotaList(
        page: page,
        perPage: _perPage,  // Request 10 data
      );

      setState(() {
        // REPLACE current data (bukan addAll!)
        _currentPageData = response.data;  
        
        // Update pagination info
        _currentPage = response.meta.currentPage;
        _lastPage = response.meta.lastPage;
        _total = response.meta.total;
        _from = response.meta.from;
        _to = response.meta.to;
        _perPage = response.meta.perPage;
        
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  void _nextPage() {
    if (_currentPage < _lastPage) {
      _loadPage(_currentPage + 1);
    }
  }

  void _previousPage() {
    if (_currentPage > 1) {
      _loadPage(_currentPage - 1);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Anggota'),
      ),
      body: Column(
        children: [
          // Data Table
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _currentPageData.isEmpty
                    ? const Center(child: Text('No data'))
                    : ListView.builder(
                        itemCount: _currentPageData.length,
                        itemBuilder: (context, index) {
                          final anggota = _currentPageData[index];
                          return ListTile(
                            title: Text(anggota.nama ?? 'N/A'),
                            subtitle: Text('NO_AGT: ${anggota.noAgt}'),
                            trailing: Text('ID_KS: ${anggota.idKs ?? "-"}'),
                          );
                        },
                      ),
          ),
          
          // Pagination Controls
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              border: Border(top: BorderSide(color: Colors.grey[300]!)),
            ),
            child: Column(
              children: [
                // "Showing 1-10 of 156"
                Text(
                  'Showing $_from-$_to of $_total records',
                  style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                ),
                
                const SizedBox(height: 8),
                
                // Previous | Page X of Y | Next
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Previous Button
                    ElevatedButton.icon(
                      onPressed: _currentPage > 1 && !_isLoading
                          ? _previousPage
                          : null,
                      icon: const Icon(Icons.chevron_left, size: 18),
                      label: const Text('Previous'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                      ),
                    ),
                    
                    const SizedBox(width: 16),
                    
                    // Page X of Y
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey[300]!),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        'Page $_currentPage of $_lastPage',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    
                    const SizedBox(width: 16),
                    
                    // Next Button
                    ElevatedButton.icon(
                      onPressed: _currentPage < _lastPage && !_isLoading
                          ? _nextPage
                          : null,
                      icon: const Icon(Icons.chevron_right, size: 18),
                      label: const Text('Next'),
                      iconAlignment: IconAlignment.end,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
```

**Cara Kerja:**
1. **Initial load**: Request `page=1&per_page=10` → dapat 10 data
2. **User klik Next**: Request `page=2&per_page=10` → **REPLACE** dengan 10 data baru (bukan tambah!)
3. **User klik Previous**: Request `page=1&per_page=10` → kembali ke 10 data page 1
4. **Memory usage**: Selalu 10 data (current page saja)

**Bedanya dengan Pattern B (Infinite Scroll):**
- Pattern B: Data **akumulasi** (20 → 40 → 60 → ...)
- Pattern D: Data **diganti** (10 → 10 → 10 → ...) ✅ Memory tetap!

**Bedanya dengan Pattern C (Load All):**
- Pattern C: Load **semua 156 data** sekaligus di awal
- Pattern D: Load **10 data** per request sesuai page yang dipilih ✅ Ringan!

---

## 📊 Comparison Table: 4 Pagination Patterns

| Pattern | Use Case | Initial Load | Memory Usage | Navigation | Backend Load | Best For |
|---------|----------|--------------|--------------|------------|--------------|----------|
| **A. Firebird Autocomplete** | Import form | 0 data | 0-100 data (search result) | Search min 3 chars | On-demand search | Form input dengan autocomplete |
| **B. Infinite Scroll** | Tabel besar | 20 data | Grows: 20→40→60 | Auto-load on scroll | Per-scroll request | Social media feed, long lists |
| **C. Load All** | Master kecil | All pages (loop) | All data (156) | Instant (data sudah ada) | 4 requests upfront | Dropdown, small master tables |
| **D. Server Pagination** ⭐ | Tabel dengan Next/Prev | 10 data | Fixed: 10 data | Click Next/Prev | Per-click request | **Tables dengan pagination UI** |

**Rekomendasi:**
- **Pattern D** untuk tabel dengan UI navigasi page (seperti screenshot Anda) ✅
- **Pattern B** untuk infinite scroll seperti social media
- **Pattern C** untuk dropdown/master data kecil (<500 records)
- **Pattern A** untuk form autocomplete dari Firebird

---

## 7. API Endpoints

### Firebird Endpoints (Import dari Firebird)

| Method | Endpoint | Deskripsi | Default Limit |
|--------|----------|-----------|---------------|
| GET | `/import-anggota-firebird?search=xxx` | Search anggota (min 3 chars) | Max 100 |
| GET | `/import-anggota-firebird/{noAgt}` | Preview single anggota | - |
| POST | `/import-anggota-firebird` | Import to MySQL | - |

**Request Body untuk Import:**
```json
{
  "no_agt": "016005000000003",
  "confirm_import": true
}
```

### MySQL Database Endpoints (CRUD)

**Semua endpoint menggunakan pagination dengan default 15 records per page**

| Method | Endpoint | Deskripsi | Default per_page |
|--------|----------|-----------|------------------|
| GET | `/anggota` | List anggota | 15 |
| GET | `/anggota/{noAgt}` | Detail anggota | - |
| POST | `/anggota` | Create anggota | - |
| PUT | `/anggota/{noAgt}` | Update anggota | - |
| DELETE | `/anggota/{noAgt}` | Delete anggota | - |
| GET | `/data-ao` | List Area Office | 15 |
| GET | `/data-lo` | List Loan Officer | 15 |
| GET | `/data-kunjungan` | List Kunjungan | 15 |
| GET | `/data-pengelola` | List Pengelola | 15 |
| GET | `/kel-sah` | List Kelompok Sahaja | 15 |
| GET | `/data-jlh-keluarga` | List Jumlah Keluarga | 15 |
| GET | `/data-penghasilan` | List Penghasilan | 15 |
| GET | `/data-trs` | List Transaksi | 15 |
| GET | `/activity-logs` | List Activity Logs | 10 (max 50) |

### Special Endpoints

| Method | Endpoint | Deskripsi | Limit |
|--------|----------|-----------|-------|
| GET | `/dashboard` | Dashboard data (Target + Realisasi) | **TANPA LIMIT** ⚠️ |

**⚠️ Warning:** Endpoint `/dashboard` **tidak menggunakan pagination**, akan return semua data!

### Cara Menggunakan Pagination

**Default (15 data):**
```http
GET /api/anggota
```

**Custom per_page:**
```http
GET /api/anggota?per_page=50
GET /api/data-kunjungan?per_page=30&page=2
```

**Dengan filter:**
```http
GET /api/anggota?NO_AGT=xxx&per_page=20
GET /api/data-ao?ID_AO=xxx&per_page=25
GET /api/activity-logs?resource_type=anggota&per_page=50
```

---

## 8. Testing dengan Postman

**Get Token:**
```http
POST http://127.0.0.1:8000/api/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```

**Search Anggota:**
```http
GET http://127.0.0.1:8000/api/import-anggota-firebird?search=fidelis
Authorization: Bearer YOUR_TOKEN
```

**Preview:**
```http
GET http://127.0.0.1:8000/api/import-anggota-firebird/016005000000003
Authorization: Bearer YOUR_TOKEN
```

**Import:**
```http
POST http://127.0.0.1:8000/api/import-anggota-firebird
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "no_agt": "016005000000003",
  "confirm_import": true
}
```

---

**That's it! 🚀**
