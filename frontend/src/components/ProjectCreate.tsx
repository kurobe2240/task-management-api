import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Paper,
  Typography,
  TextField,
  Button,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  SelectChangeEvent,
} from '@mui/material';
import axiosInstance from '../lib/axios';

interface ProjectForm {
  name: string;
  description: string;
  status: 'active' | 'completed' | 'on_hold';
}

const ProjectCreate = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState<ProjectForm>({
    name: '',
    description: '',
    status: 'active',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSelectChange = (e: SelectChangeEvent) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const validateForm = () => {
    if (!formData.name.trim()) {
      setError('プロジェクト名を入力してください');
      return false;
    }
    if (!formData.description.trim()) {
      setError('説明を入力してください');
      return false;
    }
    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!validateForm()) {
      return;
    }

    setLoading(true);

    try {
      await axiosInstance.post('/projects', formData);
      navigate('/projects');
    } catch (err) {
      setError('プロジェクトの作成に失敗しました');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        新規プロジェクト作成
      </Typography>

      <Paper elevation={2} sx={{ p: 3 }}>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        <Box component="form" onSubmit={handleSubmit}>
          <TextField
            fullWidth
            required
            label="プロジェクト名"
            name="name"
            value={formData.name}
            onChange={handleChange}
            margin="normal"
          />

          <TextField
            fullWidth
            required
            label="説明"
            name="description"
            value={formData.description}
            onChange={handleChange}
            margin="normal"
            multiline
            rows={4}
          />

          <FormControl fullWidth margin="normal">
            <InputLabel>状態</InputLabel>
            <Select
              name="status"
              value={formData.status}
              label="状態"
              onChange={handleSelectChange}
            >
              <MenuItem value="active">進行中</MenuItem>
              <MenuItem value="on_hold">保留中</MenuItem>
              <MenuItem value="completed">完了</MenuItem>
            </Select>
          </FormControl>

          <Box sx={{ mt: 3, display: 'flex', gap: 2 }}>
            <Button
              type="submit"
              variant="contained"
              disabled={loading}
              sx={{ flex: 1 }}
            >
              {loading ? '作成中...' : '作成'}
            </Button>
            <Button
              variant="outlined"
              onClick={() => navigate('/projects')}
              sx={{ flex: 1 }}
            >
              キャンセル
            </Button>
          </Box>
        </Box>
      </Paper>
    </Box>
  );
};

export default ProjectCreate;
