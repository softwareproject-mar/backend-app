# Pattern D: Server Pagination dengan Next/Previous Buttons

**⭐ Recommended Pattern untuk Tabel dengan UI Navigasi Page**

---

## 📸 Screenshot Model UI

```
┌─────────────────────────────────────────────────┐
│  [ < Previous ]  Page 1 of 8  [ Next > ]       │
│  Showing 1-10 of 156 records                    │
└─────────────────────────────────────────────────┘
```

---

## ✅ Keunggulan Pattern D

| Aspek | Deskripsi |
|-------|-----------|
| **Memory Efficiency** | Hanya simpan 10-20 data (current page saja) |
| **Fast Navigation** | User bisa lompat page dengan cepat |
| **UI/UX** | Cocok untuk tabel dengan Previous/Next buttons |
| **Scalability** | Aman untuk 1000+ data (tidak load sekaligus) |
| **Backend Load** | Per-click request (1 request = 10-20 data) |

---

## 🆚 Perbandingan dengan Pattern Lain

### Pattern D vs Pattern B (Infinite Scroll)

| Aspek | Pattern D (Server Pagination) | Pattern B (Infinite Scroll) |
|-------|------------------------------|----------------------------|
| **Data Flow** | Data **REPLACE** (10 → 10 → 10) | Data **AKUMULASI** (20 → 40 → 60) |
| **Memory** | Fixed: 10 data | Growing: 20, 40, 60, 80... |
| **Trigger** | Manual (klik Next/Prev) | Auto (scroll ke bawah) |
| **Best For** | Table dengan pagination UI | Social feed, infinite list |

### Pattern D vs Pattern C (Load All)

| Aspek | Pattern D (Server Pagination) | Pattern C (Load All) |
|-------|------------------------------|---------------------|
| **Initial Load** | 10 data (page 1) | All 156 data (loop all pages) |
| **Memory** | Fixed: 10 data | All: 156 data |
| **Requests** | 1 request per page | 4-5 requests upfront |
| **Best For** | Large tables (1000+ data) | Small master (<500 data) |

---

## 🎯 Use Case yang Cocok

✅ **Gunakan Pattern D jika:**
- Tabel dengan tombol Previous/Next
- Data banyak (1000+ records)
- User tidak perlu lihat semua data sekaligus
- Memory efficiency penting
- UI menampilkan "Page X of Y" dan "Showing 1-10 of 156"

❌ **JANGAN gunakan Pattern D jika:**
- Butuh infinite scroll (gunakan Pattern B)
- Data sedikit <100 records (gunakan Pattern C)
- Dropdown/autocomplete (gunakan Pattern A)

---

## 📦 Backend Configuration

### Endpoint Setting

**Recommended per_page:**
- **10-15 data** untuk mobile
- **15-20 data** untuk desktop

**Contoh Request:**
```http
GET /api/anggota?page=1&per_page=10
GET /api/anggota?page=2&per_page=10
```

**Response Structure:**
```json
{
  "data": [/* 10 items */],
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 10,
    "total": 156,
    "per_page": 10,
    "last_page": 16
  },
  "links": {
    "first": "http://api.com/anggota?page=1",
    "last": "http://api.com/anggota?page=16",
    "prev": null,
    "next": "http://api.com/anggota?page=2"
  }
}
```

---

## 🔧 Flutter Implementation

### 1. Service Layer (Reusable)

```dart
// lib/services/anggota_service.dart
import 'package:dio/dio.dart';
import '../models/paginated_response.dart';
import '../models/anggota.dart';
import 'api_client.dart';

class AnggotaService {
  final Dio _dio = ApiClient().dio;

  Future<PaginatedResponse<Anggota>> getAnggotaList({
    int page = 1,
    int perPage = 10,  // 10 data per page
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
```

---

### 2. Screen dengan Pagination UI

```dart
// lib/screens/anggota_table_screen.dart
import 'package:flutter/material.dart';
import '../services/anggota_service.dart';
import '../models/anggota.dart';

class AnggotaTableScreen extends StatefulWidget {
  const AnggotaTableScreen({Key? key}) : super(key: key);

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
    _loadPage(_currentPage);  // Load page 1
  }

  /// Load specific page (REPLACE data, bukan tambah)
  Future<void> _loadPage(int page) async {
    setState(() => _isLoading = true);

    try {
      final response = await _service.getAnggotaList(
        page: page,
        perPage: _perPage,
      );

      setState(() {
        // 🔴 PENTING: REPLACE current data (bukan addAll!)
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
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  /// Navigate to next page
  void _nextPage() {
    if (_currentPage < _lastPage) {
      _loadPage(_currentPage + 1);
    }
  }

  /// Navigate to previous page
  void _previousPage() {
    if (_currentPage > 1) {
      _loadPage(_currentPage - 1);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Data Anggota'),
        actions: [
          if (_total > 0)
            Center(
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Text(
                  'Total: $_total',
                  style: const TextStyle(fontSize: 14),
                ),
              ),
            ),
        ],
      ),
      body: Column(
        children: [
          // Data Table
          Expanded(
            child: _buildDataTable(),
          ),
          
          // Pagination Controls
          _buildPaginationControls(),
        ],
      ),
    );
  }

  /// Data Table Widget
  Widget _buildDataTable() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_currentPageData.isEmpty) {
      return const Center(
        child: Text(
          'No data available',
          style: TextStyle(fontSize: 16, color: Colors.grey),
        ),
      );
    }

    return ListView.builder(
      itemCount: _currentPageData.length,
      itemBuilder: (context, index) {
        final anggota = _currentPageData[index];
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: ListTile(
            leading: CircleAvatar(
              child: Text('${index + 1 + _from - 1}'),
            ),
            title: Text(
              anggota.nama ?? 'N/A',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('NO_AGT: ${anggota.noAgt}'),
                if (anggota.idKs != null)
                  Text('ID_KS: ${anggota.idKs}'),
              ],
            ),
            trailing: IconButton(
              icon: const Icon(Icons.arrow_forward_ios, size: 16),
              onPressed: () {
                // Navigate to detail
              },
            ),
          ),
        );
      },
    );
  }

  /// Pagination Controls Widget
  Widget _buildPaginationControls() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        border: Border(
          top: BorderSide(color: Colors.grey[300]!),
        ),
      ),
      child: Column(
        children: [
          // "Showing 1-10 of 156 records"
          Text(
            'Showing $_from-$_to of $_total records',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[700],
            ),
          ),
          
          const SizedBox(height: 12),
          
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
                  color: Colors.white,
                  border: Border.all(color: Colors.grey[300]!),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  'Page $_currentPage of $_lastPage',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
              ),
              
              const SizedBox(width: 16),
              
              // Next Button
              ElevatedButton.icon(
                onPressed: _currentPage < _lastPage && !_isLoading
                    ? _nextPage
                    : null,
                label: const Text('Next'),
                icon: const Icon(Icons.chevron_right, size: 18),
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
    );
  }
}
```

---

## 🎬 Cara Kerja Flow

### Initial Load
```
User buka screen
    ↓
_loadPage(1) dipanggil
    ↓
Request: GET /api/anggota?page=1&per_page=10
    ↓
Backend response: 10 data + meta (total 156, last_page 16)
    ↓
State update: _currentPageData = 10 data
    ↓
UI tampilkan: "Showing 1-10 of 156 records", "Page 1 of 16"
```

### User Klik Next
```
User klik "Next"
    ↓
_nextPage() dipanggil
    ↓
_loadPage(2) dipanggil
    ↓
Request: GET /api/anggota?page=2&per_page=10
    ↓
Backend response: 10 data baru (record 11-20)
    ↓
State update: _currentPageData = 10 data BARU (REPLACE, bukan tambah!)
    ↓
UI tampilkan: "Showing 11-20 of 156 records", "Page 2 of 16"
```

### User Klik Previous
```
User klik "Previous"
    ↓
_previousPage() dipanggil
    ↓
_loadPage(1) dipanggil
    ↓
Request: GET /api/anggota?page=1&per_page=10
    ↓
Backend response: 10 data pertama (record 1-10)
    ↓
State update: _currentPageData = 10 data pertama
    ↓
UI tampilkan: "Showing 1-10 of 156 records", "Page 1 of 16"
```

---

## 🔑 Key Points (PENTING!)

### 1. Data REPLACE, Bukan Akumulasi
```dart
// ✅ BENAR (Pattern D)
_currentPageData = response.data;  // Replace

// ❌ SALAH (ini Pattern B - Infinite Scroll)
_currentPageData.addAll(response.data);  // Accumulate
```

### 2. Memory Usage
- **Pattern D**: Selalu 10 data di memory (current page)
- **Pattern B**: Grows 20 → 40 → 60 → ... (akumulasi)
- **Pattern C**: 156 data di memory (all data)

### 3. Button States
```dart
// Previous: disabled jika page = 1
onPressed: _currentPage > 1 && !_isLoading ? _previousPage : null

// Next: disabled jika page = last_page
onPressed: _currentPage < _lastPage && !_isLoading ? _nextPage : null
```

### 4. Loading State
```dart
// Disable tombol saat loading
_currentPage > 1 && !_isLoading ? _previousPage : null
```

---

## 🧪 Testing Checklist

- [ ] Initial load menampilkan page 1 (1-10 of 156)
- [ ] Tombol Previous disabled di page 1
- [ ] Klik Next → page 2 (11-20 of 156)
- [ ] Klik Next lagi → page 3 (21-30 of 156)
- [ ] Klik Previous → kembali ke page 2
- [ ] Tombol Next disabled di last page
- [ ] "Showing X-Y of Z" update sesuai page
- [ ] "Page X of Y" update sesuai page
- [ ] Loading indicator muncul saat request
- [ ] Tombol disabled saat loading

---

## 📊 Performance Comparison

| Scenario | Pattern D | Pattern B | Pattern C |
|----------|-----------|-----------|-----------|
| **156 data total** | | | |
| Initial request | 1 request (10 data) | 1 request (20 data) | 4 requests (all data) |
| Memory usage | 10 data | 20 data (grows) | 156 data |
| Navigate to page 5 | 5 requests total | 5 requests (100 data) | 0 (already loaded) |
| **1000 data total** | | | |
| Initial request | 1 request (10 data) | 1 request (20 data) | 20+ requests (all) |
| Memory usage | 10 data | 20 data (grows) | 1000 data ⚠️ |
| Navigate to page 50 | 50 requests total | 50 requests (1000 data) | 0 (already loaded) |

**Kesimpulan:**
- **Pattern D**: Best untuk data banyak dengan UI navigasi manual
- **Pattern B**: Best untuk social feed / infinite scroll
- **Pattern C**: Best untuk master kecil (<500 data)

---

## 🚀 Quick Start

1. **Copy Service** dari section "Service Layer"
2. **Copy Screen** dari section "Screen dengan Pagination UI"
3. **Adjust model** `Anggota` sesuai struktur backend Anda
4. **Set per_page** sesuai kebutuhan (10-20 recommended)
5. **Test** dengan data real dari backend

---

## 🎨 UI Variations

### Variation 1: Minimalis (Current)
```
[ < Previous ]  Page 1 of 8  [ Next > ]
Showing 1-10 of 156 records
```

### Variation 2: Dengan Page Input
```
[ < Previous ]  Page [1▼] of 8  [ Next > ]
```

### Variation 3: Dengan per_page Selector
```
[ < Previous ]  Page 1 of 8  [ Next > ]
Show: [10▼] per page
Showing 1-10 of 156 records
```

**Implementasi Variation 2 & 3:** Bisa ditambahkan nanti sesuai kebutuhan!

---

**Created:** January 2026  
**Pattern:** Server Pagination (Pattern D)  
**Status:** Production Ready ✅
