<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: #0B4EA2;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        header .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0B4EA2;
            color: white;
        }

        .btn-primary:hover {
            background-color: #094080;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .card {
            background: white;
            border-radius: 6px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #0B4EA2;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .error-list {
            margin: 10px 0 0 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0B4EA2;
            color: #0B4EA2;
        }

        .language-section {
            margin-bottom: 30px;
        }

        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <h1>{{ __('elections.admin.candidate.add_title') }}</h1>
                <x-admin-language-switcher />
            </div>
            <div class="subtitle">{{ __('elections.admin.candidate.to') }} {{ $election->title_en }}</div>
        </div>
    </header>

    <div class="container">
        @if($errors->any())
            <div class="alert alert-error">
                <strong>{{ __('elections.admin.candidate.fix_errors') }}</strong>
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <form action="{{ route('admin.candidates.store', $election->id) }}" method="POST">
                @csrf

                <div class="language-section">
                    <div class="section-title">{{ __('elections.admin.candidate.english_version') }}</div>

                    <div class="form-group">
                        <label for="name_en">{{ __('elections.admin.candidate.name_en') }}</label>
                        <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description_en">{{ __('elections.admin.candidate.description_en') }}</label>
                        <textarea id="description_en" name="description_en">{{ old('description_en') }}</textarea>
                        <div class="help-text">{{ __('elections.admin.candidate.bio_help') }}</div>
                    </div>
                </div>

                <div class="language-section">
                    <div class="section-title">{{ __('elections.admin.candidate.slovak_version') }}</div>

                    <div class="form-group">
                        <label for="name_sk">{{ __('elections.admin.candidate.name_sk') }}</label>
                        <input type="text" id="name_sk" name="name_sk" value="{{ old('name_sk') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description_sk">{{ __('elections.admin.candidate.description_sk') }}</label>
                        <textarea id="description_sk" name="description_sk">{{ old('description_sk') }}</textarea>
                        <div class="help-text">{{ __('elections.admin.candidate.bio_help_sk') }}</div>
                    </div>
                </div>

                <div class="language-section">
                    <div class="section-title">{{ __('elections.admin.candidate.display_options') }}</div>

                    <div class="form-group">
                        <label for="display_order">{{ __('elections.admin.candidate.display_order') }}</label>
                        <input type="number" id="display_order" name="display_order" value="{{ old('display_order') }}" min="0">
                        <div class="help-text">{{ __('elections.admin.candidate.display_order_help') }}</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ __('elections.admin.candidate.submit_add') }}</button>
                    <a href="{{ route('admin.elections.show', $election->id) }}" class="btn btn-secondary">{{ __('elections.admin.candidate.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
