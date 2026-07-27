// api_service.dart
// Exemple de service Flutter pour consommer l'API Laravel "Moto Manager API".
// Ajoutez le package http dans pubspec.yaml :
//   dependencies:
//     http: ^1.2.0

import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  // Remplacez par l'URL de votre API déployée sur Render, ex :
  // https://moto-manager-api.onrender.com/api
  static const String baseUrl = 'https://moto-manager-api.onrender.com/api';

  String? _token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  /// Connexion : récupère et stocke le token Sanctum pour les appels suivants.
  Future<void> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: _headers,
      body: jsonEncode({'email': email, 'password': password}),
    );

    final body = jsonDecode(response.body);

    if (response.statusCode == 200) {
      _token = body['data']['token'];
    } else {
      throw Exception(body['message'] ?? 'Échec de connexion');
    }
  }

  /// Récupère la liste des motos (paginée).
  Future<List<dynamic>> getMotos({String? recherche, String? statut}) async {
    final query = <String, String>{
      if (recherche != null) 'recherche': recherche,
      if (statut != null) 'statut': statut,
    };
    final uri = Uri.parse('$baseUrl/motos').replace(queryParameters: query);

    final response = await http.get(uri, headers: _headers);
    final body = jsonDecode(response.body);

    if (response.statusCode == 200) {
      return body['data']['data']; // pagination Laravel : data.data = liste
    }
    throw Exception(body['message'] ?? 'Erreur lors du chargement des motos');
  }

  /// Crée une nouvelle moto.
  Future<Map<String, dynamic>> creerMoto(Map<String, dynamic> payload) async {
    final response = await http.post(
      Uri.parse('$baseUrl/motos'),
      headers: _headers,
      body: jsonEncode(payload),
    );
    final body = jsonDecode(response.body);

    if (response.statusCode == 201) {
      return body['data'];
    }
    throw Exception(body['message'] ?? 'Erreur lors de la création');
  }

  /// Récupère le tableau de bord.
  Future<Map<String, dynamic>> getDashboard() async {
    final response = await http.get(Uri.parse('$baseUrl/dashboard'), headers: _headers);
    final body = jsonDecode(response.body);

    if (response.statusCode == 200) {
      return body['data'];
    }
    throw Exception(body['message'] ?? 'Erreur lors du chargement du dashboard');
  }

  /// Enregistre un versement.
  Future<Map<String, dynamic>> enregistrerVersement({
    required int motoId,
    required String dateVersement,
    required String periodicite,
    required double montantAttendu,
    required double montantVerse,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/versements'),
      headers: _headers,
      body: jsonEncode({
        'moto_id': motoId,
        'date_versement': dateVersement,
        'periodicite': periodicite,
        'montant_attendu': montantAttendu,
        'montant_verse': montantVerse,
      }),
    );
    final body = jsonDecode(response.body);

    if (response.statusCode == 201) {
      return body['data'];
    }
    throw Exception(body['message'] ?? 'Erreur lors de l\'enregistrement du versement');
  }

  void logout() {
    _token = null;
  }
}
